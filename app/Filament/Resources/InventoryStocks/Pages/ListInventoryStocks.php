<?php

namespace App\Filament\Resources\InventoryStocks\Pages;

use App\Enums\AssetStatus;
use App\Filament\Resources\InventoryStocks\InventoryStockResource;
use App\Models\Asset;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class ListInventoryStocks extends ListRecords
{
    protected static string $resource = InventoryStockResource::class;

    #[Url(as: 'wh')]
    public ?int $selectedWarehouseId = null;

    #[Url(as: 'group')]
    public ?string $selectedGroup = null;

    public function getTitle(): string
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isWarehouseScoped() && $user->warehouse) {
            $agency = $user->warehouse->agency;
            $agencySuffix = $agency ? " ({$agency->name})" : ' (Tổng công ty HQ)';

            return 'Tồn kho thiết bị • '.$user->warehouse->name.$agencySuffix;
        }

        if ($this->selectedWarehouseId) {
            $warehouse = Warehouse::with('agency')->find($this->selectedWarehouseId);
            if ($warehouse) {
                $agencySuffix = $warehouse->agency ? " ({$warehouse->agency->name})" : ' (Tổng công ty HQ)';

                return 'Tồn kho • '.$warehouse->name.$agencySuffix;
            }
        }

        if ($this->selectedGroup === 'agency') {
            return 'Tồn kho thiết bị • Tất cả kho Đại lý';
        }

        return 'Tồn kho thiết bị (Tất cả kho & đại lý)';
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if ($this->selectedWarehouseId) {
            $query->where('assets.current_warehouse_id', $this->selectedWarehouseId);
        } elseif ($this->selectedGroup === 'agency') {
            $query->whereHas('currentWarehouse.agency');
        }

        return $query;
    }

    public function selectAll(): void
    {
        $user = Auth::user();
        if (! ($user instanceof User && $user->getScopedWarehouseId())) {
            $this->selectedWarehouseId = null;
        }
        $this->selectedGroup = null;
        $this->resetTable();
    }

    public function selectWarehouse(?int $warehouseId): void
    {
        $this->selectedWarehouseId = $warehouseId;
        $this->selectedGroup = null;
        $this->resetTable();
    }

    public function selectAgencyGroup(): void
    {
        $this->selectedWarehouseId = null;
        $this->selectedGroup = 'agency';
        $this->resetTable();
    }

    public function selectAgencyWarehouse(int $warehouseId): void
    {
        $this->selectedWarehouseId = $warehouseId;
        $this->selectedGroup = 'agency';
        $this->resetTable();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.resources.inventory-stocks.pages.tabs-dropdown'),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    /**
     * @return array{grand_total: int, hq_total: int, agency_total: int, hq_warehouses: array<array{id: int, name: string, code: ?string, total: int}>, agency_warehouses: array<array{id: int, name: string, code: ?string, agency_name: ?string, agency_code: ?string, total: int}>}
     */
    public function getTreeData(): array
    {
        $user = Auth::user();
        $scopedWhId = $user instanceof User ? $user->getScopedWarehouseId() : null;

        $warehouseQuery = Warehouse::query()
            ->with('agency')
            ->where('is_active', true);

        if ($scopedWhId) {
            $warehouseQuery->where('id', $scopedWhId);
        }

        $warehouses = $warehouseQuery->orderBy('name')->get();

        $assetCountsQuery = Asset::query()
            ->selectRaw('current_warehouse_id, count(*) as total')
            ->whereNotNull('current_warehouse_id')
            ->where('current_status', '!=', AssetStatus::NewlyAdded)
            ->groupBy('current_warehouse_id');

        if ($scopedWhId) {
            $assetCountsQuery->where('current_warehouse_id', $scopedWhId);
        }

        $counts = $assetCountsQuery->pluck('total', 'current_warehouse_id');

        $hqWarehouses = [];
        $agencyWarehouses = [];
        $grandTotal = 0;
        $hqTotal = 0;
        $agencyTotal = 0;

        foreach ($warehouses as $wh) {
            $whTotal = (int) ($counts[$wh->id] ?? 0);
            $grandTotal += $whTotal;
            $agency = $wh->agency;

            if ($agency) {
                $agencyTotal += $whTotal;
                $agencyWarehouses[] = [
                    'id' => $wh->id,
                    'name' => $wh->name,
                    'code' => $wh->code,
                    'agency_name' => $agency->name,
                    'agency_code' => $agency->code,
                    'total' => $whTotal,
                ];
            } else {
                $hqTotal += $whTotal;
                $hqWarehouses[] = [
                    'id' => $wh->id,
                    'name' => $wh->name,
                    'code' => $wh->code,
                    'total' => $whTotal,
                ];
            }
        }

        return [
            'grand_total' => $grandTotal,
            'hq_total' => $hqTotal,
            'agency_total' => $agencyTotal,
            'hq_warehouses' => $hqWarehouses,
            'agency_warehouses' => $agencyWarehouses,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
