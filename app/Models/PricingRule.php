<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_line_id',
        'customer_type',
        'base_price_per_unit_per_day',
        'min_days',
        'max_days',
        'discount_percent',
        'crew_rate_per_person_per_day',
        'transport_rate_per_km',
        'accessory_rate_per_m2',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'base_price_per_unit_per_day' => 'decimal:2',
            'min_days' => 'integer',
            'max_days' => 'integer',
            'discount_percent' => 'decimal:2',
            'crew_rate_per_person_per_day' => 'decimal:2',
            'transport_rate_per_km' => 'decimal:2',
            'accessory_rate_per_m2' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ProductLine, $this>
     */
    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }
}
