<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\DeviceType;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available asset count for a specific device type in a given warehouse and date range
     */
    public function getAvailableCount(
        int $deviceTypeId,
        Carbon|string $startDate,
        Carbon|string $endDate,
        ?int $warehouseId = null,
        ?int $excludeOrderId = null
    ): int {
        $from = Carbon::parse($startDate)->startOfDay();
        $to = Carbon::parse($endDate)->endOfDay();

        // 1. Total active inventory for this device type
        $totalStockQuery = Asset::where('device_type_id', $deviceTypeId)
            ->where('current_status', '!=', AssetStatus::Disposed);

        if ($warehouseId) {
            $totalStockQuery->where('current_warehouse_id', $warehouseId);
        }

        $totalStock = $totalStockQuery->count();

        // 2. Total units already booked in overlapping active orders
        $bookedQuery = OrderItem::where('device_type_id', $deviceTypeId)
            ->whereHas('order', function ($query) use ($from, $to, $warehouseId, $excludeOrderId) {
                $query->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Completed])
                    ->where(function ($dateQ) use ($from, $to) {
                        $dateQ->where('request_date', '<=', $to)
                            ->where(function ($sub) use ($from) {
                                $sub->whereNull('expected_return_date')
                                    ->orWhere('expected_return_date', '>=', $from);
                            });
                    });

                if ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                }

                if ($excludeOrderId) {
                    $query->where('id', '!=', $excludeOrderId);
                }
            });

        $bookedQuantity = (int) $bookedQuery->sum('quantity_required');

        // 3. Active unexpired soft/hard reservations (for quotations not yet converted to orders)
        $reservationQuery = InventoryReservation::active()
            ->overlapping($from, $to)
            ->where('device_type_id', $deviceTypeId)
            ->whereNull('order_id');

        if ($warehouseId) {
            $reservationQuery->where(function ($wQ) use ($warehouseId) {
                $wQ->whereNull('warehouse_id')->orWhere('warehouse_id', $warehouseId);
            });
        }

        $reservedQuantity = (int) $reservationQuery->sum('quantity');

        return max(0, $totalStock - $bookedQuantity - $reservedQuantity);
    }

    /**
     * Check availability for a list of BOM items (e.g. from a quotation or order)
     *
     * @param  array<int, array{device_type_id: int, quantity: int|float, description?: string}>  $items
     * @return array{
     *     has_conflicts: bool,
     *     conflicts: array<int, array{
     *         device_type_name: string,
     *         requested: int,
     *         available: int,
     *         shortage: int
     *     }>
     * }
     */
    public function checkBomAvailability(
        array $items,
        Carbon|string $startDate,
        Carbon|string $endDate,
        ?int $warehouseId = null,
        ?int $excludeOrderId = null
    ): array {
        $conflicts = [];

        foreach ($items as $item) {
            $dtId = (int) ($item['device_type_id'] ?? 0);
            $qty = (int) ceil($item['quantity'] ?? 0);

            if ($dtId <= 0 || $qty <= 0) {
                continue;
            }

            $available = $this->getAvailableCount($dtId, $startDate, $endDate, $warehouseId, $excludeOrderId);

            if ($available < $qty) {
                $deviceType = DeviceType::find($dtId);
                $conflicts[] = [
                    'device_type_name' => $deviceType?->name ?? "Thiết bị #{$dtId}",
                    'requested' => $qty,
                    'available' => $available,
                    'shortage' => $qty - $available,
                ];
            }
        }

        return [
            'has_conflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts,
        ];
    }
}
