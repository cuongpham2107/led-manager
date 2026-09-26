<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Asset
 */
class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial_no' => $this->serial_no,
            'qr_code' => $this->qr_code,
            'size' => $this->size,
            'manufactured_date' => $this->manufactured_date?->format('Y-m-d'),
            'purchase_cost' => (float) $this->purchase_cost,
            'current_status' => [
                'value' => $this->current_status->value,
                'label' => $this->current_status->getLabel(),
                'color' => $this->current_status->getColor(),
            ],
            'product_line' => $this->productLine ? [
                'id' => $this->productLine->id,
                'name' => $this->productLine->name,
                'pitch' => $this->productLine->pitch,
                'environment' => $this->productLine->environment?->value,
            ] : null,
            'led_configuration' => $this->ledConfiguration ? [
                'id' => $this->ledConfiguration->id,
                'name' => $this->ledConfiguration->name,
                'label' => $this->ledConfiguration->label,
                'receiving_card' => $this->ledConfiguration->receiving_card,
                'scan_mode' => $this->ledConfiguration->scan_mode?->value,
                'controller_model' => $this->ledConfiguration->controller_model,
            ] : null,
            'current_warehouse' => $this->currentWarehouse ? [
                'id' => $this->currentWarehouse->id,
                'code' => $this->currentWarehouse->code,
                'name' => $this->currentWarehouse->name,
            ] : null,
        ];
    }
}
