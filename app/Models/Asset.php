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
        'device_type_id',
        'size',
        'manufactured_date',
        'purchase_cost',
        'accumulated_depreciation',
        'useful_life_months',
        'depreciation_method',
        'salvage_value',
        'purchase_date',
        'current_status',
        'current_warehouse_id',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
     * @return BelongsTo<ProductLine, $this>
     */
    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    /**
     * @return BelongsTo<DeviceType, $this>
     */
    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function currentWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'current_warehouse_id');
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
