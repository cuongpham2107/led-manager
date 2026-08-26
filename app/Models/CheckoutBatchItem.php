<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CheckoutBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkout_batch_id',
        'asset_id',
        'is_dispatched',
        'dispatched_by',
        'dispatched_at',
        'checked_brightness',
        'checked_dead_pixels',
        'checked_color',
        'checked_power',
        'checklist_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_dispatched' => 'boolean',
            'dispatched_at' => 'datetime',
            'checked_brightness' => 'boolean',
            'checked_dead_pixels' => 'boolean',
            'checked_color' => 'boolean',
            'checked_power' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CheckoutBatch, $this>
     */
    public function checkoutBatch(): BelongsTo
    {
        return $this->belongsTo(CheckoutBatch::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dispatchedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    /**
     * @return HasOne<ReturnBatchItem, $this>
     */
    public function returnBatchItem(): HasOne
    {
        return $this->hasOne(ReturnBatchItem::class, 'checkout_batch_item_id');
    }
}
