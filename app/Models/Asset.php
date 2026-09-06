<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_no',
        'qr_code',
        'product_line_id',
        'size',
        'operating_hours',
        'rental_count',
        'manufactured_date',
        'purchase_cost',
        'accumulated_depreciation',
        'useful_life_months',
        'depreciation_method',
        'salvage_value',
        'purchase_date',
        'current_status',
        'current_warehouse_id',
        'warehouse_location_id',
        'note',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function (Asset $asset) {
            if (empty($asset->qr_code) && ! empty($asset->serial_no)) {
                $asset->qr_code = "LED-{$asset->serial_no}";
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operating_hours' => 'integer',
            'rental_count' => 'integer',
            'manufactured_date' => 'date',
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'useful_life_months' => 'integer',
            'salvage_value' => 'decimal:2',
            'current_status' => AssetStatus::class,
        ];
    }

    /**
     * Get current book value (Giá trị còn lại sổ sách)
     */
    public function getCurrentBookValueAttribute(): float
    {
        $cost = (float) ($this->purchase_cost ?? 0);
        $dep = (float) ($this->accumulated_depreciation ?? 0);

        return max(0, $cost - $dep);
    }

    /**
     * Calculate monthly depreciation rate (Mức khấu hao 1 tháng)
     */
    public function getMonthlyDepreciationAttribute(): float
    {
        $cost = (float) ($this->purchase_cost ?? 0);
        $salvage = (float) ($this->salvage_value ?? 0);
        $months = (int) ($this->useful_life_months ?: 36);

        if ($months <= 0 || $cost <= $salvage) {
            return 0;
        }

        return round(($cost - $salvage) / $months, 2);
    }

    /**
     * Get location label formatted as 'Warehouse · Location' or 'Repair bay'
     */
    public function getLocationLabelAttribute(): string
    {
        if ($this->current_status === AssetStatus::Repairing) {
            return 'Khu sửa chữa';
        }

        if (! $this->currentWarehouse) {
            return 'Chưa xác định';
        }

        if ($this->warehouseLocation) {
            return "{$this->currentWarehouse->name} · {$this->warehouseLocation->name}";
        }

        return $this->currentWarehouse->name;
    }

    /**
     * @return BelongsTo<ProductLine, $this>
     */
    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function currentWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'current_warehouse_id');
    }

    /**
     * @return BelongsTo<WarehouseLocation, $this>
     */
    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    /**
     * @return BelongsTo<WarehouseLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    /**
     * @return HasMany<AssetStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(AssetStatusLog::class);
    }

    /**
     * @return HasMany<RepairLog, $this>
     */
    public function repairLogs(): HasMany
    {
        return $this->hasMany(RepairLog::class);
    }

    /**
     * @return HasMany<CheckinBatchItem, $this>
     */
    public function checkinBatchItems(): HasMany
    {
        return $this->hasMany(CheckinBatchItem::class);
    }

    /**
     * @return HasMany<CheckoutBatchItem, $this>
     */
    public function checkoutBatchItems(): HasMany
    {
        return $this->hasMany(CheckoutBatchItem::class);
    }

    /**
     * @return HasMany<ReturnBatchItem, $this>
     */
    public function returnBatchItems(): HasMany
    {
        return $this->hasMany(ReturnBatchItem::class);
    }
}
