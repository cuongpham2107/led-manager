<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $warehouse = $this->warehouse ?? $this->agency?->warehouse;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => (bool) $this->is_active,
            'warehouse' => $warehouse ? [
                'id' => $warehouse->id,
                'code' => $warehouse->code,
                'name' => $warehouse->name,
                'city' => $warehouse->city,
            ] : null,
            'agency' => $this->agency ? [
                'id' => $this->agency->id,
                'name' => $this->agency->name,
                'code' => $this->agency->code,
                'is_active' => (bool) $this->agency->is_active,
            ] : null,
            'is_agency' => $this->isAgencyScoped(),
            'roles' => $this->roles->pluck('name'),
        ];
    }
}
