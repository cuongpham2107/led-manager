<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckinBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkin_batch_id',
        'asset_id',
        'condition',
        'condition_note',
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
     * @return BelongsTo<CheckinBatch, $this>
     */
    public function checkinBatch(): BelongsTo
    {
        return $this->belongsTo(CheckinBatch::class);
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
    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
