<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CheckinBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'warehouse_id',
        'note',
        'expected_date',
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
            'expected_date' => 'date',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CheckinBatchItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CheckinBatchItem::class);
    }

    /**
     * @return BelongsToMany<Asset, $this>
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'checkin_batch_items')
            ->withPivot(['condition', 'condition_note', 'is_received', 'received_by', 'received_at'])
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
