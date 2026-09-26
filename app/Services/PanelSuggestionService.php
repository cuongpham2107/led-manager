<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\Asset;
use App\Models\CheckinBatchItem;
use App\Models\LedConfiguration;
use App\Models\ProductLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Gợi ý tấm LED "đúng bộ" khi xuất kho: tính số tấm theo kích thước màn,
 * liệt kê cấu hình LED còn đủ hàng và chọn serial ưu tiên cùng lô nhập.
 */
class PanelSuggestionService
{
    private const FALLBACK_MODULE_MM = 500;

    /**
     * @return array{cols: int, rows: int, required: int, area_m2: float}
     */
    public function gridFor(ProductLine $line, float $widthM, float $heightM): array
    {
        $moduleW = (float) $line->module_width_mm ?: self::FALLBACK_MODULE_MM;
        $moduleH = (float) $line->module_height_mm ?: self::FALLBACK_MODULE_MM;

        // round() trước ceil() để 1.5 m / 0.5 m không thành 3.0000000001 cột
        $cols = max(1, (int) ceil(round($widthM * 1000 / $moduleW, 6)));
        $rows = max(1, (int) ceil(round($heightM * 1000 / $moduleH, 6)));

        return [
            'cols' => $cols,
            'rows' => $rows,
            'required' => $cols * $rows,
            'area_m2' => round($cols * $moduleW * $rows * $moduleH / 1_000_000, 2),
        ];
    }

    /**
     * @param  array{receiving_card?: ?string, scan_mode?: ?string, controller_model?: ?string}  $filters
     * @return Collection<int, array{configuration: LedConfiguration, available: int, is_enough: bool, lots: array<int|string, int>}>
     */
    public function suggest(ProductLine $line, int $warehouseId, int $required, array $filters = []): Collection
    {
        $configurations = $line->ledConfigurations()
            ->where('is_active', true)
            ->when(filled($filters['receiving_card'] ?? null), fn ($q) => $q->where('receiving_card', $filters['receiving_card']))
            ->when(filled($filters['scan_mode'] ?? null), fn ($q) => $q->where('scan_mode', $filters['scan_mode']))
            ->when(filled($filters['controller_model'] ?? null), fn ($q) => $q->where('controller_model', $filters['controller_model']))
            ->get();

        return $configurations
            ->map(function (LedConfiguration $configuration) use ($warehouseId, $required): array {
                $lots = $this->lotCounts($configuration, $warehouseId);
                $available = array_sum($lots);

                return [
                    'configuration' => $configuration,
                    'available' => $available,
                    'is_enough' => $available >= $required,
                    'lots' => $lots,
                ];
            })
            ->sortBy([['is_enough', 'desc'], ['available', 'desc']])
            ->values();
    }

    /**
     * Chọn serial cho một cấu hình: lấy lô có nhiều tấm nhất trước để một lô phủ được cả màn.
     *
     * @param  array<int, int>  $excludeIds
     * @return array{assets: Collection<int, Asset>, mixed_lots: bool}
     */
    public function pickAssets(LedConfiguration $configuration, int $warehouseId, int $qty, array $excludeIds = []): array
    {
        $candidates = $this->configurationQuery($configuration, $warehouseId)
            ->when($excludeIds !== [], fn ($q) => $q->whereNotIn('assets.id', $excludeIds))
            ->orderBy('serial_no')
            ->get();

        $byLot = $candidates->groupBy(fn (Asset $asset) => $asset->lot_id ?? 'none')
            ->sortByDesc(fn (Collection $group) => $group->count());

        $picked = collect();
        $lotsUsed = 0;
        foreach ($byLot as $group) {
            if ($picked->count() >= $qty) {
                break;
            }
            $lotsUsed++;
            $picked = $picked->merge($group->take($qty - $picked->count()));
        }

        return ['assets' => $picked->values(), 'mixed_lots' => $lotsUsed > 1];
    }

    /**
     * Tấm sẵn sàng trong kho và chưa bị giữ bởi một phiếu xuất đang mở.
     *
     * @return Builder<Asset>
     */
    public static function availableQuery(int $warehouseId): Builder
    {
        return Asset::query()
            ->where('current_warehouse_id', $warehouseId)
            ->where('current_status', AssetStatus::Ready)
            ->whereDoesntHave('checkoutBatchItems', function ($q) {
                $q->whereHas('checkoutBatch', fn ($b) => $b->where('status', '!=', BatchStatus::Cancelled))
                    ->where(function ($subQ) {
                        $subQ->where('is_dispatched', false)
                            ->orWhereDoesntHave('returnBatchItem', fn ($r) => $r->where('is_received', true));
                    });
            });
    }

    /**
     * @return Builder<Asset>
     */
    private function configurationQuery(LedConfiguration $configuration, int $warehouseId): Builder
    {
        return static::availableQuery($warehouseId)
            ->where('led_configuration_id', $configuration->id)
            ->addSelect(['lot_id' => CheckinBatchItem::query()
                ->selectRaw('max(checkin_batch_id)')
                ->whereColumn('checkin_batch_items.asset_id', 'assets.id'),
            ]);
    }

    /**
     * @return array<int|string, int>
     */
    private function lotCounts(LedConfiguration $configuration, int $warehouseId): array
    {
        return $this->configurationQuery($configuration, $warehouseId)
            ->get()
            ->countBy(fn (Asset $asset) => $asset->lot_id ?? 'none')
            ->all();
    }
}
