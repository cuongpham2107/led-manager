<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\DeviceType;
use App\Models\InventoryReservation;
use App\Models\Quotation;
use Illuminate\Support\Collection;

class InventoryReservationService
{
    /**
     * Soft lock: temporarily reserve inventory for a quotation (e.g. 24-48 hours)
     *
     * @return Collection<int, InventoryReservation>
     */
    public function softLock(Quotation $quotation, int $durationHours = 48): Collection
    {
        $this->release((int) $quotation->id);

        $startDate = $quotation->event_start_date ?? now()->toDateString();
        $endDate = $quotation->event_end_date ?? $startDate;
        $expiresAt = now()->addHours($durationHours);

        $reservations = collect();

        // 1. If quotation has explicit items, reserve them
        $items = $quotation->items;
        if ($items->isNotEmpty()) {
            foreach ($items as $item) {
                if ($item->device_type_id && $item->quantity > 0) {
                    $reservations->push(InventoryReservation::create([
                        'quotation_id' => $quotation->id,
                        'device_type_id' => $item->device_type_id,
                        'quantity' => (int) ceil($item->quantity),
                        'lock_type' => 'soft',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'expires_at' => $expiresAt,
                        'status' => 'active',
                        'note' => "Soft lock cho Báo giá [{$quotation->code}]",
                    ]));
                }
            }
        } else {
            // 2. Fallback: reserve estimated cabinets if configured
            if ($quotation->estimated_cabinet_qty > 0) {
                $cabinetType = DeviceType::where('code', 'CAB')->first();
                if ($cabinetType) {
                    $reservations->push(InventoryReservation::create([
                        'quotation_id' => $quotation->id,
                        'device_type_id' => $cabinetType->id,
                        'quantity' => (int) $quotation->estimated_cabinet_qty,
                        'lock_type' => 'soft',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'expires_at' => $expiresAt,
                        'status' => 'active',
                        'note' => "Soft lock định mức cabinet Báo giá [{$quotation->code}]",
                    ]));
                }
            }
        }

        return $reservations;
    }

    /**
     * Hard lock: confirmed reservation after contract deposit/approval
     *
     * @return Collection<int, InventoryReservation>
     */
    public function hardLock(Contract $contract): Collection
    {
        $order = $contract->order;
        $quotation = $contract->quotation;

        $startDate = $contract->start_date
            ?? $order?->request_date
            ?? $quotation?->event_start_date
            ?? now()->toDateString();

        $endDate = $contract->end_date
            ?? $order?->expected_return_date
            ?? $quotation?->event_end_date
            ?? $startDate;

        $reservations = collect();

        // Release existing soft locks for the quotation
        if ($quotation) {
            InventoryReservation::where('quotation_id', $quotation->id)
                ->where('lock_type', 'soft')
                ->where('status', 'active')
                ->update(['status' => 'converted']);
        }

        if ($order && $order->items()->exists()) {
            foreach ($order->items as $item) {
                if ($item->device_type_id && $item->quantity_required > 0) {
                    $reservations->push(InventoryReservation::create([
                        'quotation_id' => $quotation?->id,
                        'order_id' => $order->id,
                        'device_type_id' => $item->device_type_id,
                        'warehouse_id' => $order->warehouse_id,
                        'quantity' => (int) $item->quantity_required,
                        'lock_type' => 'hard',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'expires_at' => null,
                        'status' => 'active',
                        'note' => "Hard lock cho Hợp đồng [{$contract->code}]",
                    ]));
                }
            }
        }

        return $reservations;
    }

    /**
     * Release active reservations for a quotation
     */
    public function release(int $quotationId): void
    {
        InventoryReservation::where('quotation_id', $quotationId)
            ->where('status', 'active')
            ->update(['status' => 'released']);
    }

    /**
     * Release all expired soft locks
     */
    public function cleanupExpired(): int
    {
        return InventoryReservation::where('status', 'active')
            ->where('lock_type', 'soft')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'released']);
    }
}
