<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'customer_id',
        'sales_user_id',
        'screen_width_m',
        'screen_height_m',
        'screen_area_m2',
        'product_line_id',
        'rental_days',
        'crew_size',
        'transport_distance_km',
        'event_start_date',
        'event_end_date',
        'event_name',
        'location',
        'estimated_cabinet_qty',
        'estimated_processor_qty',
        'estimated_load_kg',
        'estimated_power_kw',
        'equipment_cost',
        'crew_rate',
        'labour_cost',
        'transport_rate',
        'transport_cost',
        'accessory_cost',
        'total_cost',
        'discount_amount',
        'total_price',
        'margin_percent',
        'status',
        'lost_reason',
        'converted_order_id',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'screen_width_m' => 'decimal:2',
            'screen_height_m' => 'decimal:2',
            'screen_area_m2' => 'decimal:2',
            'rental_days' => 'integer',
            'crew_size' => 'integer',
            'transport_distance_km' => 'decimal:2',
            'crew_rate' => 'decimal:2',
            'transport_rate' => 'decimal:2',
            'event_start_date' => 'date',
            'event_end_date' => 'date',
            'estimated_cabinet_qty' => 'integer',
            'estimated_processor_qty' => 'integer',
            'estimated_load_kg' => 'decimal:2',
            'estimated_power_kw' => 'decimal:2',
            'equipment_cost' => 'decimal:2',
            'labour_cost' => 'decimal:2',
            'transport_cost' => 'decimal:2',
            'accessory_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'margin_percent' => 'decimal:2',
            'status' => QuotationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function salesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    /**
     * @return BelongsTo<ProductLine, $this>
     */
    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    /**
     * @return HasMany<QuotationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
