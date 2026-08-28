<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ReturnBatchItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReturnBatchItem
 */
class ReturnBatchItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'is_received' => (bool) $this->is_received,
            'grade' => [
                'value' => $this->grade->value,
                'label' => $this->grade->getLabel(),
                'color' => $this->grade->getColor(),
            ],
            'grade_note' => $this->grade_note,
            'received_at' => $this->received_at?->toIso8601String(),
            'received_by' => $this->receivedBy ? [
                'id' => $this->receivedBy->id,
                'name' => $this->receivedBy->name,
            ] : null,
            'asset' => $this->asset ? new AssetResource($this->asset) : null,
        ];
    }
}
