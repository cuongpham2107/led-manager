<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CheckinBatchItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CheckinBatchItem
 */
class CheckinBatchItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'condition' => $this->condition,
            'condition_note' => $this->condition_note,
            'is_received' => (bool) $this->is_received,
            'received_at' => $this->received_at?->toIso8601String(),
            'received_by' => $this->receivedByUser ? [
                'id' => $this->receivedByUser->id,
                'name' => $this->receivedByUser->name,
            ] : null,
            'asset' => $this->asset ? new AssetResource($this->asset) : null,
        ];
    }
}
