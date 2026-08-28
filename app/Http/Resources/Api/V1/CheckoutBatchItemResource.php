<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CheckoutBatchItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CheckoutBatchItem
 */
class CheckoutBatchItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'is_dispatched' => (bool) $this->is_dispatched,
            'dispatched_at' => $this->dispatched_at?->toIso8601String(),
            'dispatched_by' => $this->dispatchedBy ? [
                'id' => $this->dispatchedBy->id,
                'name' => $this->dispatchedBy->name,
            ] : null,
            'asset' => $this->asset ? new AssetResource($this->asset) : null,
        ];
    }
}
