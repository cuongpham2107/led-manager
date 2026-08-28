<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\ReturnGrade;
use App\Models\ReturnBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReturnBatch
 */
class ReturnBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalItems = $this->items()->count();
        $normalCount = $this->items()->where('grade', ReturnGrade::Normal)->count();
        $damagedCount = $this->items()->where('grade', ReturnGrade::Damaged)->count();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->getLabel(),
                'color' => $this->status->getColor(),
            ],
            'return_date' => $this->return_date?->format('Y-m-d'),
            'note' => $this->note,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'total_items_count' => $totalItems,
            'normal_count' => $normalCount,
            'damaged_count' => $damagedCount,
            'checkout_batch' => $this->checkoutBatch ? [
                'id' => $this->checkoutBatch->id,
                'code' => $this->checkoutBatch->code,
                'warehouse' => $this->checkoutBatch->warehouse ? [
                    'id' => $this->checkoutBatch->warehouse->id,
                    'name' => $this->checkoutBatch->warehouse->name,
                ] : null,
                'order' => $this->checkoutBatch->order ? [
                    'id' => $this->checkoutBatch->order->id,
                    'order_no' => $this->checkoutBatch->order->order_no,
                    'event' => $this->checkoutBatch->order->event,
                ] : null,
                'customer' => $this->checkoutBatch->customer ? [
                    'id' => $this->checkoutBatch->customer->id,
                    'name' => $this->checkoutBatch->customer->name,
                ] : null,
            ] : null,
            'items' => ReturnBatchItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
