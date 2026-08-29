<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CheckinBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CheckinBatch
 */
class CheckinBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $scannedCount = $this->items()->where('is_received', true)->count();
        $totalItemsCount = $this->items()->count();
        $targetCount = max((int) $this->quantity, $totalItemsCount);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->getLabel(),
                'color' => $this->status->getColor(),
            ],
            'batch_type' => [
                'value' => $this->batch_type->value,
                'label' => $this->batch_type->getLabel(),
                'color' => $this->batch_type->getColor(),
            ],
            'quantity' => (int) $this->quantity,
            'target_items_count' => $targetCount,
            'scanned_count' => $scannedCount,
            'progress_percent' => $targetCount > 0 ? min(100, (int) round(($scannedCount / $targetCount) * 100)) : 0,
            'expected_date' => $this->expected_date?->format('Y-m-d'),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'production_note' => $this->production_note,
            'note' => $this->note,
            'warehouse' => $this->warehouse ? [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ] : null,
            'product_line' => $this->productLine ? [
                'id' => $this->productLine->id,
                'code' => $this->productLine->code,
                'name' => $this->productLine->name,
            ] : null,
            'device_type' => $this->deviceType ? [
                'id' => $this->deviceType->id,
                'name' => $this->deviceType->name,
            ] : null,
            'items' => CheckinBatchItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
