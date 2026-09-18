<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
     * Uses a single raw SQL query instead of loading all assets into memory.
     */
    public function getCurrentInventoryAreaAttribute(): float
    {
        if (! $this->warehouse_id) {
            return 0.0;
        }

        $result = DB::selectOne(
            <<<'SQL'
                SELECT COALESCE(SUM(
                    CASE
                        WHEN pl.module_width_mm > 0 AND pl.module_height_mm > 0
                        THEN (pl.module_width_mm / 1000.0) * (pl.module_height_mm / 1000.0)
                        ELSE 0.25
                    END
                ), 0) AS total_area
                FROM assets a
                LEFT JOIN product_lines pl ON pl.id = a.product_line_id
                WHERE a.current_warehouse_id = ?
            SQL,
            [$this->warehouse_id],
        );

        return round((float) $result->total_area, 2);
    }

    /**
     * Check if adding more area would exceed the allocated quota.
     */
    public function wouldExceedQuota(float $additionalAreaM2): bool
    {
        return ($this->current_inventory_area + $additionalAreaM2) > (float) $this->allocated_area_m2;
    }

    /**
     * Get remaining quota capacity in m².
     */
    public function getRemainingQuotaM2(): float
    {
        return max(0, (float) $this->allocated_area_m2 - $this->current_inventory_area);
    }
}
