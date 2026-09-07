<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CheckoutBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'order_id',
        'customer_id',
        'warehouse_id',
        'required_area_m2',
        'export_date',
        'expected_return_date',
        'purpose',
        'note',
        'status',
        'created_by',
        'dispatched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_area_m2' => 'decimal:2',
            'export_date' => 'date',
            'expected_return_date' => 'date',
            'dispatched_at' => 'datetime',
            'status' => BatchStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CheckoutBatchItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CheckoutBatchItem::class);
    }

    /**
     * @return BelongsToMany<Asset, $this>
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'checkout_batch_items')
            ->withPivot(['is_dispatched', 'dispatched_by', 'dispatched_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ReturnBatch, $this>
     */
    public function returnBatches(): HasMany
    {
        return $this->hasMany(ReturnBatch::class);
    }

    /**
     * @return MorphMany<AssetStatusLog, $this>
     */
    public function statusLogs(): MorphMany
    {
        return $this->morphMany(AssetStatusLog::class, 'source');
    }
}
