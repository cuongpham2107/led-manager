<?php

namespace App\Filament\Pages;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ProductLine;
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

                TextColumn::make('warehouseLocation.name')
                    ->label('VỊ TRÍ / ZONE')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Chưa xếp'),

                TextColumn::make('size')
                    ->label('KÍCH THƯỚC')
                    ->sortable()
                    ->placeholder('500×500 mm'),

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
                    ->hidden(fn (): bool => (bool) auth()->user()?->getScopedWarehouseId())
                    ->preload(),

                SelectFilter::make('warehouse_location_id')
                    ->label('Vị trí kho')
                    ->relationship('warehouseLocation', 'name', modifyQueryUsing: function ($query) {
                        if ($whId = auth()->user()?->getScopedWarehouseId()) {
                            $query->where('warehouse_id', $whId);
                        }
                    })
                    ->preload(),

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
                'Vị trí',
                'Kích thước',
                'Trạng thái',
            ]);

            Asset::with(['productLine', 'currentWarehouse', 'warehouseLocation'])
                ->chunk(200, function ($assets) use ($handle) {
                    foreach ($assets as $a) {
                        fputcsv($handle, [
                            $a->serial_no,
                            $a->productLine?->name ?? '—',
                            $a->currentWarehouse?->name ?? 'Chưa gán kho',
                            $a->warehouseLocation?->name ?? '—',
                            $a->size ?? '500×500 mm',
                            $a->current_status instanceof AssetStatus ? $a->current_status->getLabel() : (string) $a->current_status,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
