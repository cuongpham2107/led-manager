<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'province',
        'contact_person',
        'phone',
        'address',
        'commission_rate',
        'allocated_area_m2',
        'warehouse_id',
        'is_active',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'allocated_area_m2' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<PricingRule, $this>
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    /**
     * Calculate current total inventory area (m²) stored in this agency's warehouse.
     */
    public function getCurrentInventoryAreaAttribute(): float
    {
        if (! $this->warehouse_id) {
            return 0.0;
        }

        $assets = Asset::where('current_warehouse_id', $this->warehouse_id)
            ->with('productLine')
            ->get();

        return round($assets->sum(fn (Asset $a) => $a->area_m2), 2);
    }

    /**
     * Calculate current active rented screen area (m²) for this agency.
     */
    public function getCurrentRentedAreaAttribute(): float
    {
        return (float) $this->orders()
            ->whereIn('status', [OrderStatus::OutboundCreated, OrderStatus::Dispatched])
            ->sum('area_m2');
    }
}
