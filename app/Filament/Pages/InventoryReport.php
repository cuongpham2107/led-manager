<?php

namespace App\Filament\Pages;

use App\Enums\AssetStatus;
use App\Models\Agency;
use App\Models\Asset;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class InventoryReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Tồn kho';

    protected static ?string $title = 'Báo Cáo Tồn Kho Thiết Bị';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.inventory-report';

    public function getStats(): array
    {
        $whId = auth()->user()?->getScopedWarehouseId();
        $base = Asset::query();
        if ($whId) {
            $base->where('current_warehouse_id', $whId);
        }

        $total = (clone $base)->count();
        $ready = (clone $base)->where('current_status', AssetStatus::Ready)->count();
        $inEvent = (clone $base)->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();
        $repairing = (clone $base)->where('current_status', AssetStatus::Repairing)->count();
        $missing = (clone $base)->where('current_status', AssetStatus::Missing)->count();

        $readyRate = $total > 0 ? round(($ready / $total) * 100, 1) : 0;
        $inEventRate = $total > 0 ? round(($inEvent / $total) * 100, 1) : 0;

        $readyArea = (clone $base)->where('current_status', AssetStatus::Ready)
            ->with('productLine')
            ->get()
            ->sum(function (Asset $a) {
                $w = ($a->productLine?->module_width_mm ?? 500) / 1000;
                $h = ($a->productLine?->module_height_mm ?? 500) / 1000;

                return $w * $h;
            });

        return [
            'total' => $total,
            'ready' => $ready,
            'ready_rate' => $readyRate,
            'in_event' => $inEvent,
            'in_event_rate' => $inEventRate,
            'repairing' => $repairing,
            'missing' => $missing,
            'ready_area' => round($readyArea, 1),
        ];
    }

    public function getWarehouseBreakdown(): array
    {
        $query = Warehouse::with(['assets.productLine']);
        if ($whId = auth()->user()?->getScopedWarehouseId()) {
            $query->where('id', $whId);
        }

        return $query->get()
            ->map(function (Warehouse $warehouse) {
                $assets = $warehouse->assets;
                $total = $assets->count();
                $ready = $assets->where('current_status', AssetStatus::Ready)->count();
                $inEvent = $assets->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();
                $repairing = $assets->where('current_status', AssetStatus::Repairing)->count();

                $readyRate = $total > 0 ? round(($ready / $total) * 100, 1) : 0;

                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'total' => $total,
                    'ready' => $ready,
                    'ready_rate' => $readyRate,
                    'in_event' => $inEvent,
                    'repairing' => $repairing,
                ];
            })
            ->toArray();
    }

    public function getProductLineBreakdown(): array
    {
        return ProductLine::with(['assets'])
            ->get()
            ->map(function (ProductLine $line) {
                $assets = $line->assets;
                $total = $assets->count();
                $ready = $assets->where('current_status', AssetStatus::Ready)->count();
                $inEvent = $assets->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();
                $repairing = $assets->where('current_status', AssetStatus::Repairing)->count();

                $readyRate = $total > 0 ? round(($ready / $total) * 100, 1) : 0;

                $w = ($line->module_width_mm ?? 500) / 1000;
                $h = ($line->module_height_mm ?? 500) / 1000;
                $readyArea = round($ready * ($w * $h), 1);

                return [
                    'id' => $line->id,
                    'name' => $line->name,
                    'code' => $line->code,
                    'total' => $total,
                    'ready' => $ready,
                    'ready_rate' => $readyRate,
                    'in_event' => $inEvent,
                    'repairing' => $repairing,
                    'ready_area' => $readyArea,
                ];
            })
            ->toArray();
    }

    public function table(Table $table): Table
    {
        $query = Asset::query()->with(['productLine', 'currentWarehouse', 'warehouseLocation']);
        if ($whId = auth()->user()?->getScopedWarehouseId()) {
            $query->where('current_warehouse_id', $whId);
        }

        return $table
            ->query($query)
            ->defaultSort('serial_no')
            ->columns([
                TextColumn::make('serial_no')
                    ->label('SỐ SERI')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('productLine.name')
                    ->label('DÒNG SẢN PHẨM')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('currentWarehouse.name')
                    ->label('KHO HIỆN TẠI')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('agency')
                    ->label('ĐẠI LÝ / ĐƠN VỊ')
                    ->state(function (Asset $record): string {
                        $agency = $record->currentWarehouse?->agency;

                        return $agency ? "{$agency->name} ({$agency->code})" : 'Tổng công ty (HQ)';
                    })
                    ->badge()
                    ->color(fn (Asset $record): string => $record->currentWarehouse?->agency ? 'warning' : 'gray'),

                TextColumn::make('size')
                    ->label('KÍCH THƯỚC')
                    ->sortable()
                    ->placeholder('500 x 500 mm'),

                TextColumn::make('current_status')
                    ->label('TRẠNG THÁI')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof AssetStatus ? $state->getLabel() : ($state?->value ?? (string) $state))
                    ->color(fn ($state) => $state instanceof AssetStatus ? $state->getColor() : 'gray'),

                TextColumn::make('manufacture_date')
                    ->label('NGÀY SẢN XUẤT')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('current_warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('currentWarehouse', 'name')
                    ->hidden(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User && (bool) $user->getScopedWarehouseId();
                    })
                    ->preload(),

                SelectFilter::make('agency_filter')
                    ->label('Đại lý / Đơn vị')
                    ->options(function (): array {
                        $options = ['hq' => 'Tổng công ty (HQ)'];
                        $agencies = Agency::query()->orderBy('name')->pluck('name', 'id')->toArray();
                        foreach ($agencies as $id => $name) {
                            $options[(string) $id] = $name;
                        }

                        return $options;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $val = $data['value'] ?? null;
                        if (! $val) {
                            return $query;
                        }

                        if ($val === 'hq') {
                            return $query->whereHas('currentWarehouse', function ($wq) {
                                $wq->whereDoesntHave('agency');
                            });
                        }

                        return $query->whereHas('currentWarehouse.agency', function ($aq) use ($val) {
                            $aq->where('id', (int) $val);
                        });
                    })
                    ->hidden(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User && (bool) $user->getScopedWarehouseId();
                    }),

                SelectFilter::make('product_line_id')
                    ->label('Dòng sản phẩm')
                    ->relationship('productLine', 'name')
                    ->preload(),

                SelectFilter::make('current_status')
                    ->label('Trạng thái')
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
            'Content-Disposition' => 'attachment; filename="bao-cao-ton-kho-'.now()->format('Ymd-His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'Số Seri',
                'Dòng sản phẩm',
                'Kho hiện tại',
                'Đại lý / Đơn vị',
                'Kích thước',
                'Trạng thái',
            ]);

            Asset::with(['productLine', 'currentWarehouse.agency'])
                ->chunk(200, function ($assets) use ($handle) {
                    foreach ($assets as $a) {
                        $agency = $a->currentWarehouse?->agency;
                        fputcsv($handle, [
                            $a->serial_no,
                            $a->productLine?->name ?? '—',
                            $a->currentWarehouse?->name ?? 'Chưa gán kho',
                            $agency ? "{$agency->name} ({$agency->code})" : 'Tổng công ty (HQ)',
                            $a->size ?? '500 x 500 mm',
                            $a->current_status instanceof AssetStatus ? $a->current_status->getLabel() : (string) $a->current_status,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
