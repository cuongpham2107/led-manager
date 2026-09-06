<?php

namespace App\Filament\Pages;

use App\Enums\AssetStatus;
use App\Models\AssetStatusLog;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class MovementHistoryReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Lịch sử nhập/xuất';

    protected static ?string $title = 'Báo Cáo Lịch Sử Nhập / Xuất Thiết Bị';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.movement-history-report';

    /**
     * Get aggregate statistics for top cards.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $whId = auth()->user()?->getScopedWarehouseId();
        $base = AssetStatusLog::query();
        if ($whId) {
            $base->where(fn ($q) => $q->where('from_warehouse_id', $whId)
                ->orWhere('to_warehouse_id', $whId)
                ->orWhereHas('asset', fn ($aq) => $aq->where('current_warehouse_id', $whId)));
        }

        $total = (clone $base)->count();

        $checkins = (clone $base)->where(function ($q) {
            $q->where('source_type', 'like', '%Checkin%')
                ->orWhere(function ($sub) {
                    $sub->where('to_status', AssetStatus::Ready)
                        ->whereNull('from_status');
                });
        })->count();

        $checkouts = (clone $base)->where(function ($q) {
            $q->where('source_type', 'like', '%Checkout%')
                ->orWhereIn('to_status', [AssetStatus::InTransit, AssetStatus::InEvent]);
        })->count();

        $returns = (clone $base)->where(function ($q) {
            $q->where('source_type', 'like', '%Return%');
        })->count();

        $repairs = (clone $base)->where(function ($q) {
            $q->where('to_status', AssetStatus::Repairing)
                ->orWhere('from_status', AssetStatus::Repairing);
        })->count();

        $last30Days = (clone $base)->where('created_at', '>=', now()->subDays(30))->count();

        return [
            'total' => $total,
            'checkins' => $checkins,
            'checkouts' => $checkouts,
            'returns' => $returns,
            'repairs' => $repairs,
            'last_30_days' => $last30Days,
        ];
    }

    public function table(Table $table): Table
    {
        $query = AssetStatusLog::query()->with(['asset.productLine', 'fromWarehouse', 'toWarehouse', 'changer']);
        if ($whId = auth()->user()?->getScopedWarehouseId()) {
            $query->where(fn ($q) => $q->where('from_warehouse_id', $whId)
                ->orWhere('to_warehouse_id', $whId)
                ->orWhereHas('asset', fn ($aq) => $aq->where('current_warehouse_id', $whId)));
        }

        return $table
            ->query($query)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('THỜI GIAN')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->fontFamily('mono')
                    ->size('sm'),

                TextColumn::make('asset.serial_no')
                    ->label('SỐ SERI')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('asset.productLine.name')
                    ->label('DÒNG SẢN PHẨM')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('source_type')
                    ->label('LOẠI THAO TÁC')
                    ->badge()
                    ->formatStateUsing(function ($state, AssetStatusLog $record) {
                        if (str_contains((string) $state, 'CheckinBatch')) {
                            return 'Nhập kho';
                        }
                        if (str_contains((string) $state, 'CheckoutBatch')) {
                            return 'Xuất sự kiện';
                        }
                        if (str_contains((string) $state, 'ReturnBatch')) {
                            return 'Thu hồi / Trả kho';
                        }
                        if ($record->to_status === AssetStatus::Repairing) {
                            return 'Gửi bảo dưỡng';
                        }
                        if ($record->from_status === AssetStatus::Repairing && $record->to_status === AssetStatus::Ready) {
                            return 'Xong bảo dưỡng';
                        }
                        if ($record->from_warehouse_id && $record->to_warehouse_id && $record->from_warehouse_id !== $record->to_warehouse_id) {
                            return 'Chuyển kho';
                        }

                        return 'Biến động trạng thái';
                    })
                    ->color(function ($state, AssetStatusLog $record) {
                        if (str_contains((string) $state, 'CheckinBatch')) {
                            return 'emerald';
                        }
                        if (str_contains((string) $state, 'CheckoutBatch')) {
                            return 'blue';
                        }
                        if (str_contains((string) $state, 'ReturnBatch')) {
                            return 'info';
                        }
                        if ($record->to_status === AssetStatus::Repairing) {
                            return 'warning';
                        }
                        if ($record->from_status === AssetStatus::Repairing && $record->to_status === AssetStatus::Ready) {
                            return 'success';
                        }

                        return 'gray';
                    }),

                TextColumn::make('warehouse_change')
                    ->label('BIẾN ĐỘNG KHO')
                    ->state(function (AssetStatusLog $record) {
                        $from = $record->fromWarehouse?->name;
                        $to = $record->toWarehouse?->name;

                        if ($from && $to && $from !== $to) {
                            return "{$from} → {$to}";
                        }

                        return $to ?? $from ?? '—';
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status_change')
                    ->label('TRẠNG THÁI')
                    ->state(function (AssetStatusLog $record) {
                        $fromLabel = $record->from_status instanceof AssetStatus ? $record->from_status->getLabel() : ($record->from_status?->value ?? '—');
                        $toLabel = $record->to_status instanceof AssetStatus ? $record->to_status->getLabel() : ($record->to_status?->value ?? '—');

                        if ($fromLabel !== '—' && $fromLabel !== $toLabel) {
                            return "{$fromLabel} → {$toLabel}";
                        }

                        return $toLabel;
                    }),

                TextColumn::make('changer.name')
                    ->label('NGƯỜI THỰC HIỆN')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Hệ thống'),

                TextColumn::make('note')
                    ->label('GHI CHÚ')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->placeholder('—'),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),

                SelectFilter::make('warehouse')
                    ->label('Kho tiếp nhận / liên quan')
                    ->relationship('toWarehouse', 'name')
                    ->hidden(fn (): bool => (bool) auth()->user()?->getScopedWarehouseId())
                    ->preload(),

                SelectFilter::make('to_status')
                    ->label('Trạng thái chuyển đến')
                    ->options(collect(AssetStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->headerActions([
                Action::make('export_csv')
                    ->label('Xuất CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => $this->exportCsv()),
            ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="lich-su-nhap-xuat-'.now()->format('Ymd-His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'Thời gian',
                'Số Seri',
                'Dòng sản phẩm',
                'Kho chuyển đi',
                'Kho chuyển đến',
                'Trạng thái trước',
                'Trạng thái sau',
                'Người thực hiện',
                'Ghi chú',
            ]);

            AssetStatusLog::with(['asset.productLine', 'fromWarehouse', 'toWarehouse', 'changer'])
                ->latest('created_at')
                ->chunk(200, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->created_at?->format('d/m/Y H:i') ?? '',
                            $log->asset?->serial_no ?? '',
                            $log->asset?->productLine?->name ?? '',
                            $log->fromWarehouse?->name ?? '',
                            $log->toWarehouse?->name ?? '',
                            $log->from_status instanceof AssetStatus ? $log->from_status->getLabel() : (string) $log->from_status,
                            $log->to_status instanceof AssetStatus ? $log->to_status->getLabel() : (string) $log->to_status,
                            $log->changer?->name ?? 'Hệ thống',
                            $log->note ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
