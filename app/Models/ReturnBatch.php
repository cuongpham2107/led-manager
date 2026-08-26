<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReturnBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'checkout_batch_id',
        'return_date',
        'note',
        'status',
        'created_by',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ReturnBatchItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnBatchItem::class);
    }

    /**
     * @return BelongsToMany<Asset, $this>
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'return_batch_items')
            ->withPivot(['checkout_batch_item_id', 'grade', 'grade_note', 'is_received', 'received_by', 'received_at'])
            ->withTimestamps();
    }

    /**
     * @return MorphMany<AssetStatusLog, $this>
     */
    public function statusLogs(): MorphMany
    {
        return $this->morphMany(AssetStatusLog::class, 'source');
    }
}
