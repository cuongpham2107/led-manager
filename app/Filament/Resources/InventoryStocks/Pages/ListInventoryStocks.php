<?php

namespace App\Filament\Resources\InventoryStocks\Pages;

use App\Filament\Resources\InventoryStocks\InventoryStockResource;
use App\Models\Asset;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListInventoryStocks extends ListRecords
{
    protected static string $resource = InventoryStockResource::class;

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Start;
    }

    #[Url(as: 'wh')]
    public ?int $selectedWarehouseId = null;

    #[Url(as: 'loc')]
    public ?string $selectedLocationId = null;

    public function getTitle(): string
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user?->isWarehouseScoped() && $user->warehouse) {
            return 'Tài sản trong kho • '.$user->warehouse->name;
        }

        if ($this->selectedWarehouseId) {
            $whName = Warehouse::query()->where('id', $this->selectedWarehouseId)->value('name');

            if ($this->selectedLocationId === 'unassigned') {
                return $whName.' • Chưa xếp vị trí';
            }

            if ($this->selectedLocationId) {
                $locName = WarehouseLocation::query()->where('id', (int) $this->selectedLocationId)->value('name');

                return $whName.' • '.$locName;
            }

            return 'Tài sản trong kho • '.$whName;
        }

        return 'Tất cả tài sản trong kho';
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if ($this->selectedLocationId === 'unassigned') {
            if ($this->selectedWarehouseId) {
                $query->where('assets.current_warehouse_id', $this->selectedWarehouseId);
            }
            $query->whereNull('assets.warehouse_location_id');
        } elseif ($this->selectedLocationId) {
            $query->where('assets.warehouse_location_id', (int) $this->selectedLocationId);
        } elseif ($this->selectedWarehouseId) {
            $query->where('assets.current_warehouse_id', $this->selectedWarehouseId);
        }

        return $query;
    }

    public function selectAll(): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        if (! $user?->getScopedWarehouseId()) {
            $this->selectedWarehouseId = null;
        }
        $this->selectedLocationId = null;
        $this->resetTable();
    }

    public function selectWarehouse(?int $warehouseId): void
    {
        $this->selectedWarehouseId = $warehouseId;
        $this->selectedLocationId = null;
        $this->resetTable();
    }

    public function selectLocation(?int $warehouseId, ?string $locationId): void
    {
        $this->selectedWarehouseId = $warehouseId;
        $this->selectedLocationId = $locationId;
        $this->resetTable();
    }

    /**
     * @return array<NavigationGroup | NavigationItem>
     */
    public function getSubNavigation(): array
    {
        /** @var User|null $user */
        $user = auth()->user();
        $scopedWhId = $user?->getScopedWarehouseId();

        $treeData = $this->getTreeData();
        $navigation = [];

        if (! $scopedWhId) {
            $navigation[] = NavigationItem::make('Tất cả thiết bị')
                ->icon('heroicon-o-squares-2x2')
                ->badge((string) $treeData['grand_total'])
                ->url(static::getUrl())
                ->isActiveWhen(fn (): bool => empty($this->selectedWarehouseId) && empty($this->selectedLocationId));
        }

        foreach ($treeData['warehouses'] as $wh) {
            $items = [
                NavigationItem::make('Tất cả tại '.$wh['name'])
                    ->badge((string) $wh['total'])
                    ->url(static::getUrl(['wh' => $wh['id']]))
                    ->isActiveWhen(fn (): bool => (int) $this->selectedWarehouseId === (int) $wh['id'] && empty($this->selectedLocationId)),
            ];

            foreach ($wh['locations'] as $loc) {
                $items[] = NavigationItem::make($loc['name'])
                    ->badge((string) $loc['count'])
                    ->url(static::getUrl(['wh' => $wh['id'], 'loc' => $loc['id']]))
                    ->isActiveWhen(fn (): bool => (int) $this->selectedWarehouseId === (int) $wh['id'] && (string) $this->selectedLocationId === (string) $loc['id']);
            }

            if ($wh['unassigned_count'] > 0) {
                $items[] = NavigationItem::make('Chưa xếp vị trí')
                    ->badge((string) $wh['unassigned_count'], 'warning')
                    ->url(static::getUrl(['wh' => $wh['id'], 'loc' => 'unassigned']))
                    ->isActiveWhen(fn (): bool => (int) $this->selectedWarehouseId === (int) $wh['id'] && $this->selectedLocationId === 'unassigned');
            }

            $navigation[] = NavigationGroup::make($wh['name'])
                ->icon('heroicon-o-building-storefront')
                ->collapsible()
                ->items($items);
        }

        return $navigation;
    }

    /**
     * @return array{grand_total: int, warehouses: array<array{id: int, name: string, code: ?string, total: int, locations: array<array{id: int, name: string, code: ?string, count: int}>, unassigned_count: int}>}
     */
    public function getTreeData(): array
    {
        /** @var User|null $user */
        $user = auth()->user();
        $scopedWhId = $user?->getScopedWarehouseId();

        $warehouseQuery = Warehouse::query()->where('is_active', true);
        if ($scopedWhId) {
            $warehouseQuery->where('id', $scopedWhId);
        }

        $warehouses = $warehouseQuery->orderBy('name')->get();

        $assetCountsQuery = Asset::query()
            ->selectRaw('current_warehouse_id, warehouse_location_id, count(*) as total')
            ->groupBy('current_warehouse_id', 'warehouse_location_id');

        if ($scopedWhId) {
            $assetCountsQuery->where('current_warehouse_id', $scopedWhId);
        }

        $counts = $assetCountsQuery->get();

        $locationsQuery = WarehouseLocation::query()->where('is_active', true)->orderBy('name');
        if ($scopedWhId) {
            $locationsQuery->where('warehouse_id', $scopedWhId);
        }
        $locations = $locationsQuery->get()->groupBy('warehouse_id');

        $tree = [];
        $grandTotal = 0;

        foreach ($warehouses as $wh) {
            $whCounts = $counts->where('current_warehouse_id', $wh->id);
            $whTotal = (int) $whCounts->sum('total');
            $grandTotal += $whTotal;

            $whLocations = $locations->get($wh->id, collect());
            $locationNodes = [];

            foreach ($whLocations as $loc) {
                $locCount = (int) $whCounts->where('warehouse_location_id', $loc->id)->sum('total');
                $locationNodes[] = [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'code' => $loc->code,
                    'count' => $locCount,
                ];
            }

            $unassignedCount = (int) $whCounts->whereNull('warehouse_location_id')->sum('total');

            $tree[] = [
                'id' => $wh->id,
                'name' => $wh->name,
                'code' => $wh->code,
                'total' => $whTotal,
                'locations' => $locationNodes,
                'unassigned_count' => $unassignedCount,
            ];
        }

        return [
            'grand_total' => $grandTotal,
            'warehouses' => $tree,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
