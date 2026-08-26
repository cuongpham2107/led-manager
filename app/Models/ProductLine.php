<?php

namespace App\Models;

use App\Enums\ProductEnvironment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'pixel_pitch_unit',
        'pixel_pitch',
        'environment',
        'module_width_mm',
        'module_height_mm',
        'weight_kg',
        'power_watt',
        'brand',
        'cabinet_material',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pixel_pitch' => 'decimal:2',
            'environment' => ProductEnvironment::class,
            'module_width_mm' => 'decimal:2',
            'module_height_mm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'power_watt' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<PricingRule, $this>
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }
}
