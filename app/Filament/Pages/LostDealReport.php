<?php

namespace App\Filament\Pages;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class LostDealReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo & Thống kê';

    protected static ?string $navigationLabel = 'Phân tích lý do mất deal';

    protected static ?string $title = 'Báo Cáo & Phân Tích Nguyên Nhân Báo Giá Thất Bại (Lost Deals)';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedXCircle;

    protected string $view = 'filament.pages.lost-deal-report';

    public function getStats(): array
    {
        $lost = Quotation::whereIn('status', [QuotationStatus::Rejected, QuotationStatus::Expired])->get();
        $totalDeals = $lost->count();
        $totalValue = (float) $lost->sum('total_price');
        $totalArea = (float) $lost->sum('screen_area_m2');

        $primaryReason = $lost->groupBy('lost_reason')
            ->sortByDesc(fn ($group) => $group->count())
            ->keys()
            ->first() ?? 'Chưa ghi rõ';

        return [
            'total_deals' => $totalDeals,
            'total_value' => $totalValue,
            'total_area' => $totalArea,
            'primary_reason' => $primaryReason,
        ];
    }

    public function getReasonBreakdown(): array
    {
        $lost = Quotation::whereIn('status', [QuotationStatus::Rejected, QuotationStatus::Expired])->get();
        $totalDeals = max(1, $lost->count());

        return $lost->groupBy(fn ($q) => $q->lost_reason ?: 'Khác / Chưa ghi rõ')
            ->map(function ($group, $reason) use ($totalDeals) {
                $count = $group->count();
                $value = (float) $group->sum('total_price');
                $pct = round(($count / $totalDeals) * 100, 1);

                return [
                    'reason' => $reason,
                    'count' => $count,
                    'value' => $value,
                    'pct' => $pct,
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quotation::query()
                    ->whereIn('status', ['rejected', 'expired'])
                    ->with(['customer', 'salesUser', 'productLine'])
            )
            ->columns([
                TextColumn::make('code')
                    ->label('Mã báo giá')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_name')
                    ->label('Sự kiện')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('lost_reason')
                    ->label('Lý do mất deal')
                    ->badge()
                    ->color('danger')
                    ->searchable()
                    ->placeholder('Chưa ghi rõ'),
                TextColumn::make('total_price')
                    ->label('Giá trị báo giá (VND)')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold')
                    ->summarize(
                        Sum::make()->label('Tổng giá trị thất thoát')->money('VND')
                    ),
                TextColumn::make('screen_area_m2')
                    ->label('Diện tích (m²)')
                    ->suffix(' m²')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('salesUser.name')
                    ->label('Sales phụ trách')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_start_date')
                    ->label('Ngày sự kiện')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Ghi chú')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sales_user_id')
                    ->label('Nhân viên Sales')
                    ->relationship('salesUser', 'name'),
                SelectFilter::make('lost_reason')
                    ->label('Lý do thất bại')
                    ->options([
                        'Giá cao so với đối thủ' => 'Giá cao so với đối thủ',
                        'Khách đổi kế hoạch / Hủy sự kiện' => 'Khách đổi kế hoạch / Hủy sự kiện',
                        'Thiếu số lượng thiết bị' => 'Thiếu số lượng thiết bị',
                        'Không đáp ứng tiến độ' => 'Không đáp ứng tiến độ',
                        'Khác' => 'Khác',
                    ]),
                Filter::make('event_start_date')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('event_start_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Xuất CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('danger')
                    ->action(fn () => $this->exportCsv()),
            ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="lost_deals_report_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Mã báo giá',
                'Khách hàng',
                'Sự kiện',
                'Lý do mất deal',
                'Giá trị báo giá (VND)',
                'Diện tích (m2)',
                'Sales phụ trách',
                'Ngày sự kiện',
                'Ghi chú',
            ]);

            $quotations = Quotation::whereIn('status', ['rejected', 'expired'])
                ->with(['customer', 'salesUser'])
                ->get();

            foreach ($quotations as $q) {
                fputcsv($handle, [
                    $q->code,
                    $q->customer?->name,
                    $q->event_name,
                    $q->lost_reason,
                    $q->total_price,
                    $q->screen_area_m2,
                    $q->salesUser?->name,
                    $q->event_start_date?->format('d/m/Y'),
                    $q->note,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
