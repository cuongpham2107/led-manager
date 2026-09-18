<?php

namespace App\Models;

use App\Enums\RepairResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RepairLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'agency_id',
        'start_date',
        'end_date',
        'repair_note',
        'result_status',
        'repair_cost',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'repair_cost' => 'decimal:2',
            'result_status' => RepairResultStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    protected static function booted(): void
    {
        static::creating(function (RepairLog $log) {
            if (! $log->agency_id && $log->asset_id) {
                $asset = Asset::with('currentWarehouse.agency')->find($log->asset_id);
                $log->agency_id = $asset?->currentWarehouse?->agency?->id;
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany<AssetStatusLog, $this>
     */
    public function statusLogs(): MorphMany
    {
        return $this->morphMany(AssetStatusLog::class, 'source');
    }
}
