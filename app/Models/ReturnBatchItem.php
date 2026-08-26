<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_batch_id',
        'asset_id',
        'checkout_batch_item_id',
        'grade',
        'grade_note',
        'is_received',
        'received_by',
        'received_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_received' => 'boolean',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReturnBatch, $this>
     */
    public function returnBatch(): BelongsTo
    {
        return $this->belongsTo(ReturnBatch::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<CheckoutBatchItem, $this>
     */
    public function checkoutBatchItem(): BelongsTo
    {
        return $this->belongsTo(CheckoutBatchItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
