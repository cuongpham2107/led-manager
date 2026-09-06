<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\ProductLine;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Get available asset count for a specific product line in a given warehouse and date range
     */
    public function getAvailableCount(
        int $productLineId,
        Carbon|string $startDate,
        Carbon|string $endDate,
        ?int $warehouseId = null,
        ?int $excludeOrderId = null
    ): int {
        $from = Carbon::parse($startDate)->startOfDay();
        $to = Carbon::parse($endDate)->endOfDay();

        // 1. Total active inventory for this product line
        $totalStockQuery = Asset::where('product_line_id', $productLineId)
            ->where('current_status', '!=', AssetStatus::Disposed);

        if ($warehouseId) {
            $totalStockQuery->where('current_warehouse_id', $warehouseId);
        }

        $totalStock = $totalStockQuery->count();

        // 2. Total units already booked in overlapping active orders
        $ordersQuery = Order::query()
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Completed, OrderStatus::Returned])
            ->where(function ($dateQ) use ($from, $to) {
                $dateQ->where('request_date', '<=', $to)
                    ->where(function ($sub) use ($from) {
                        $sub->whereNull('expected_return_date')
                            ->orWhere('expected_return_date', '>=', $from);
                    });
            })
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($excludeOrderId, fn ($q) => $q->where('id', '!=', $excludeOrderId))
            ->whereHas('items', fn ($q) => $q->where('product_line_id', $productLineId))
            ->with([
                'items' => fn ($q) => $q->where('product_line_id', $productLineId),
                'checkoutBatches.items.asset',
            ]);

        $bookedQuantity = 0;
        foreach ($ordersQuery->get() as $order) {
            $orderReqQty = (int) $order->items->sum('quantity_required');

            if ($order->status === OrderStatus::Dispatched) {
                $dispatchedCount = 0;
                foreach ($order->checkoutBatches as $batch) {
                    if (in_array($batch->status, [BatchStatus::Dispatched, BatchStatus::InProgress, BatchStatus::Completed])) {
                        $dispatchedCount += $batch->items
                            ->filter(fn ($item) => $item->asset?->product_line_id == $productLineId && $item->is_dispatched)
                            ->count();
                    }
                }
                $effectiveBooked = $dispatchedCount > 0 ? $dispatchedCount : min($orderReqQty, $totalStock);
            } else {
                $effectiveBooked = min($orderReqQty, $totalStock);
            }

            $bookedQuantity += $effectiveBooked;
        }

        // 3. Active unexpired soft/hard reservations (for quotations not yet converted to orders)
        $reservationQuery = InventoryReservation::active()
            ->overlapping($from, $to)
            ->where('product_line_id', $productLineId)
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
     * @param  array<int, array{product_line_id?: int, device_type_id?: int, quantity: int|float, description?: string}>  $items
     * @return array{
     *     has_conflicts: bool,
     *     conflicts: array<int, array{
     *         product_line_name: string,
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
            $plId = (int) ($item['product_line_id'] ?? $item['device_type_id'] ?? 0);
            $qty = (int) ceil($item['quantity'] ?? 0);

            if ($plId <= 0 || $qty <= 0) {
                continue;
            }

            $available = $this->getAvailableCount($plId, $startDate, $endDate, $warehouseId, $excludeOrderId);

            if ($available < $qty) {
                $productLine = ProductLine::find($plId);
                $name = $productLine?->name ?? "Dòng thiết bị #{$plId}";
                $conflicts[] = [
                    'product_line_name' => $name,
                    'device_type_name' => $name,
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
