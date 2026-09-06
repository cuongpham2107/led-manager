<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CheckoutBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CheckoutBatch
 */
class CheckoutBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cabinetsPerM2 = 4; // Standard 0.5x0.5m cabinets = 4 per m2
        $estimatedCabinetsRequired = (int) ceil(((float) $this->required_area_m2) * $cabinetsPerM2);
        $scannedCount = $this->items()->where('is_dispatched', true)->count();
        $totalItemsCount = $this->items()->count();
        $targetCount = max($estimatedCabinetsRequired, $totalItemsCount);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->getLabel(),
                'color' => $this->status->getColor(),
            ],
            'required_area_m2' => (float) $this->required_area_m2,
            'target_cabinets_count' => $targetCount,
            'scanned_count' => $scannedCount,
            'progress_percent' => $targetCount > 0 ? min(100, (int) round(($scannedCount / $targetCount) * 100)) : 0,
            'expected_return_date' => $this->expected_return_date?->format('Y-m-d'),
            'dispatched_at' => $this->dispatched_at?->toIso8601String(),
            'order' => $this->order ? [
                'id' => $this->order->id,
                'order_no' => $this->order->order_no,
                'event' => $this->order->event,
                'request_date' => $this->order->request_date?->format('Y-m-d'),
                'expected_return_date' => $this->order->expected_return_date?->format('Y-m-d'),
                'status' => $this->order->status->value,
            ] : null,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'company_name' => $this->customer->company_name,
            ] : null,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null,
            'items' => CheckoutBatchItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
