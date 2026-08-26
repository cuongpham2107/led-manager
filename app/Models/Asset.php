<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'serial_no',
        'qr_code',
        'product_line_id',
        'device_type_id',
        'size',
        'manufactured_date',
        'purchase_cost',
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
        ];
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
