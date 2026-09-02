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

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo & Thống kê';

    protected static ?string $navigationLabel = 'Tỷ lệ khai thác kho';

    protected static ?string $title = 'Báo Cáo Tỷ Lệ Khai Thác Kho & Vòng Đời Thiết Bị';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected string $view = 'filament.pages.asset-utilization-report';

    public function getStats(): array
    {
        $total = Asset::count();
        $ready = Asset::where('current_status', AssetStatus::Ready)->count();
        $inEvent = Asset::whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();
        $repairing = Asset::where('current_status', AssetStatus::Repairing)->count();
        $missing = Asset::where('current_status', AssetStatus::Missing)->count();
        $disposed = Asset::where('current_status', AssetStatus::Disposed)->count();
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
        $lines = ProductLine::with(['assets.currentWarehouse'])->get();

        return $lines->map(function ($line) {
            $assets = $line->assets;
            $total = $assets->count();
            $ready = $assets->where('current_status', AssetStatus::Ready)->count();
            $inEvent = $assets->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();
            $repairing = $assets->where('current_status', AssetStatus::Repairing)->count();
            $missing = $assets->where('current_status', AssetStatus::Missing)->count();
            $disposed = $assets->where('current_status', AssetStatus::Disposed)->count();

            $rate = $total > 0 ? round(($inEvent / $total) * 100, 1) : 0;
            $readyRate = $total > 0 ? round(($ready / $total) * 100, 1) : 0;
            $repairingRate = $total > 0 ? round(($repairing / $total) * 100, 1) : 0;

            $warehouseDistribution = $assets->groupBy(fn ($a) => $a->currentWarehouse?->name ?? 'Chưa gán kho')
                ->map(fn ($group) => $group->count());

            return [
                'id' => $line->id,
                'name' => $line->name,
                'code' => $line->code,
                'environment' => $line->environment?->value ?? 'indoor',
                'pixel_pitch' => $line->pixel_pitch_mm ?? $line->pixel_pitch ?? '—',
                'cabinet_size' => ($line->cabinet_width_mm && $line->cabinet_height_mm) ? "{$line->cabinet_width_mm}x{$line->cabinet_height_mm}mm" : '500x500mm',
                'total' => $total,
                'ready' => $ready,
                'ready_rate' => $readyRate,
                'in_event' => $inEvent,
                'repairing' => $repairing,
                'repairing_rate' => $repairingRate,
                'missing' => $missing,
                'disposed' => $disposed,
                'rate' => $rate,
                'warehouses' => $warehouseDistribution,
            ];
        })->toArray();
    }

    public function getWarehouseStats(): array
    {
        $warehouses = Warehouse::with(['assets.productLine'])->get();

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
                    ->state(fn ($record) => $record->assets()->count()),
                TextColumn::make('ready_count')
                    ->label('Sẵn sàng')
                    ->badge()
                    ->color('success')
                    ->state(fn ($record) => $record->assets()->where('current_status', AssetStatus::Ready)->count()),
                TextColumn::make('in_event_count')
                    ->label('Đang đi sự kiện')
                    ->badge()
                    ->color('info')
                    ->state(fn ($record) => $record->assets()->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count()),
                TextColumn::make('repairing_count')
                    ->label('Đang bảo trì')
                    ->badge()
                    ->color('danger')
                    ->state(fn ($record) => $record->assets()->where('current_status', AssetStatus::Repairing)->count()),
                TextColumn::make('utilization_rate')
                    ->label('Tỷ lệ khai thác (%)')
                    ->badge()
                    ->color(fn ($state) => (float) $state > 70 ? 'danger' : ((float) $state > 40 ? 'warning' : 'success'))
                    ->state(function ($record) {
                        $total = $record->assets()->count();
                        if ($total === 0) {
                            return '0%';
                        }
                        $active = $record->assets()->whereIn('current_status', [AssetStatus::InEvent, AssetStatus::InTransit])->count();

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
