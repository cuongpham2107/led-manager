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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class AssetUtilizationReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Sử dụng tài sản';

    protected static ?string $title = 'Báo Cáo Sử Dụng Tài Sản & Vòng Đời Thiết Bị';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.asset-utilization-report';

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
        $disposed = (clone $base)->where('current_status', AssetStatus::Disposed)->count();
        $rate = $total > 0 ? round(($inEvent / $total) * 100, 1) : 0;
        $readyRate = $total > 0 ? round(($ready / $total) * 100, 1) : 0;
        $repairingRate = $total > 0 ? round(($repairing / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'ready' => $ready,
            'ready_rate' => $readyRate,
            'in_event' => $inEvent,
            'utilization_rate' => $rate,
            'repairing' => $repairing,
            'repairing_rate' => $repairingRate,
            'missing' => $missing,
            'disposed' => $disposed,
        ];
    }

    public function getProductLineStats(): array
    {
        $whId = auth()->user()?->getScopedWarehouseId();
        $lines = ProductLine::with(['assets' => function ($q) use ($whId) {
            if ($whId) {
                $q->where('current_warehouse_id', $whId);
            }
        }, 'assets.currentWarehouse'])->get();

        return $lines->map(function ($line) {
            $assets = $line->assets;
            $total = $assets->count();
            $ready = $assets->where('current_status', AssetStatus::Ready)->count();
            $inEvent = $assets->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();
            $repairing = $assets->where('current_status', AssetStatus::Repairing)->count();
            $missing = $assets->where('current_status', AssetStatus::Missing)->count();
            $disposed = $assets->where('current_status', AssetStatus::Disposed)->count();

            $utilizationRate = $total > 0 ? round(($inEvent / $total) * 100, 1) : 0;
            $readyRate = $total > 0 ? round(($ready / $total) * 100, 1) : 0;
            $repairingRate = $total > 0 ? round(($repairing / $total) * 100, 1) : 0;

            $warehouseDistribution = $assets->groupBy(fn ($a) => $a->currentWarehouse?->name ?? 'Chưa gán')
                ->map(fn ($group) => $group->count());

            return [
                'id' => $line->id,
                'name' => $line->name,
                'code' => $line->code,
                'pitch' => $line->pitch ?? '—',
                'pixel_pitch' => $line->pixel_pitch_mm ?? $line->pixel_pitch ?? ($line->pitch ?? '—'),
                'cabinet_size' => ($line->cabinet_width_mm && $line->cabinet_height_mm) ? "{$line->cabinet_width_mm}x{$line->cabinet_height_mm}mm" : (($line->module_width_mm && $line->module_height_mm) ? "{$line->module_width_mm}x{$line->module_height_mm}mm" : '500x500mm'),
                'environment' => $line->environment?->value ?? 'indoor',
                'module_size' => ($line->module_width_mm && $line->module_height_mm) ? $line->module_width_mm.'×'.$line->module_height_mm.' mm' : '—',
                'total' => $total,
                'ready' => $ready,
                'ready_rate' => $readyRate,
                'in_event' => $inEvent,
                'utilization_rate' => $utilizationRate,
                'rate' => $utilizationRate,
                'repairing' => $repairing,
                'repairing_rate' => $repairingRate,
                'missing' => $missing,
                'disposed' => $disposed,
                'warehouses' => $warehouseDistribution,
            ];
        })->toArray();
    }

    public function getWarehouseStats(): array
    {
        $whId = auth()->user()?->getScopedWarehouseId();
        $query = Warehouse::with(['assets' => function ($q) use ($whId) {
            if ($whId) {
                $q->where('current_warehouse_id', $whId);
            }
        }, 'assets.productLine']);

        if ($whId) {
            $query->where('id', $whId);
        }

        $warehouses = $query->get();

        return $warehouses->map(function ($warehouse) {
            $assets = $warehouse->assets;
            $total = $assets->count();
            $ready = $assets->where('current_status', AssetStatus::Ready)->count();
            $inEvent = $assets->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();
            $repairing = $assets->where('current_status', AssetStatus::Repairing)->count();
            $missing = $assets->where('current_status', AssetStatus::Missing)->count();
            $disposed = $assets->where('current_status', AssetStatus::Disposed)->count();

            $rate = $total > 0 ? round(($inEvent / $total) * 100, 1) : 0;
            $readyRate = $total > 0 ? round(($ready / $total) * 100, 1) : 0;
            $repairingRate = $total > 0 ? round(($repairing / $total) * 100, 1) : 0;

            $productLinesDistribution = $assets->groupBy(fn ($a) => $a->productLine?->name ?? 'Khác')
                ->map(fn ($group) => $group->count());

            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'address' => $warehouse->address ?? '—',
                'phone' => $warehouse->phone ?? '—',
                'total' => $total,
                'ready' => $ready,
                'ready_rate' => $readyRate,
                'in_event' => $inEvent,
                'repairing' => $repairing,
                'repairing_rate' => $repairingRate,
                'missing' => $missing,
                'disposed' => $disposed,
                'rate' => $rate,
                'product_lines' => $productLinesDistribution,
            ];
        })->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductLine::query()->with('assets')
            )
            ->columns([
                TextColumn::make('code')
                    ->label('Mã dòng')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Dòng LED')
                    ->searchable(),
                TextColumn::make('environment')
                    ->label('Môi trường')
                    ->badge(),
                TextColumn::make('total_cabinets')
                    ->label('Tổng số lượng kho')
                    ->numeric()
                    ->state(function ($record) {
                        $q = $record->assets();
                        if ($whId = auth()->user()?->getScopedWarehouseId()) {
                            $q->where('current_warehouse_id', $whId);
                        }

                        return $q->count();
                    }),
                TextColumn::make('ready_count')
                    ->label('Sẵn sàng')
                    ->badge()
                    ->color('success')
                    ->state(function ($record) {
                        $q = $record->assets()->where('current_status', AssetStatus::Ready);
                        if ($whId = auth()->user()?->getScopedWarehouseId()) {
                            $q->where('current_warehouse_id', $whId);
                        }

                        return $q->count();
                    }),
                TextColumn::make('in_event_count')
                    ->label('Đang đi sự kiện')
                    ->badge()
                    ->color('info')
                    ->state(function ($record) {
                        $q = $record->assets()->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit]);
                        if ($whId = auth()->user()?->getScopedWarehouseId()) {
                            $q->where('current_warehouse_id', $whId);
                        }

                        return $q->count();
                    }),
                TextColumn::make('repairing_count')
                    ->label('Đang bảo trì')
                    ->badge()
                    ->color('danger')
                    ->state(function ($record) {
                        $q = $record->assets()->where('current_status', AssetStatus::Repairing);
                        if ($whId = auth()->user()?->getScopedWarehouseId()) {
                            $q->where('current_warehouse_id', $whId);
                        }

                        return $q->count();
                    }),
                TextColumn::make('utilization_rate')
                    ->label('Tỷ lệ khai thác (%)')
                    ->badge()
                    ->color(fn ($state) => (float) $state > 70 ? 'danger' : ((float) $state > 40 ? 'warning' : 'success'))
                    ->state(function ($record) {
                        $base = $record->assets();
                        if ($whId = auth()->user()?->getScopedWarehouseId()) {
                            $base->where('current_warehouse_id', $whId);
                        }
                        $total = (clone $base)->count();
                        if ($total === 0) {
                            return '0%';
                        }
                        $active = (clone $base)->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();

                        return round(($active / $total) * 100, 1).'%';
                    }),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Xuất CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(fn () => $this->exportCsv()),
            ])
            ->paginated(false);
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="asset_utilization_report_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Mã dòng',
                'Dòng LED',
                'Môi trường',
                'Tổng số lượng kho',
                'Sẵn sàng',
                'Đang đi sự kiện',
                'Đang bảo trì',
                'Tỷ lệ khai thác (%)',
            ]);

            $lines = ProductLine::with('assets')->get();
            foreach ($lines as $line) {
                $total = $line->assets()->count();
                $ready = $line->assets()->where('current_status', AssetStatus::Ready)->count();
                $inEvent = $line->assets()->where('current_status', AssetStatus::InEvent)->count();
                $repairing = $line->assets()->where('current_status', AssetStatus::Repairing)->count();
                $rate = $total > 0 ? round(($inEvent / $total) * 100, 1) : 0;

                fputcsv($handle, [
                    $line->code,
                    $line->name,
                    $line->environment?->value ?? '',
                    $total,
                    $ready,
                    $inEvent,
                    $repairing,
                    $rate.'%',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
