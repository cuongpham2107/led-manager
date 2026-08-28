<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Warehouse
 */
class WarehouseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'city' => $this->city,
            'address' => $this->address,
            'is_active' => (bool) $this->is_active,
            'assets_count' => $this->whenCounted('assets'),
            'ready_assets_count' => $this->assets()->where('current_status', 'ready')->count(),
        ];
    }
}
