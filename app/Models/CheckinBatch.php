<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckinBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'warehouse_id',
        'agency_id',
        'note',
        'expected_date',
        'batch_type',
        'product_line_id',
        'quantity',
        'production_note',
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
            'status' => BatchStatus::class,
            'batch_type' => CheckinBatchType::class,
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
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    protected static function booted(): void
    {
        static::saving(function (CheckinBatch $batch) {
            if (! $batch->agency_id && $batch->warehouse_id) {
                $batch->agency_id = Warehouse::find($batch->warehouse_id)?->agency?->id;
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
     * @return BelongsTo<ProductLine, $this>
     */
    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
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

    /**
     * Hoàn tất nhận hàng cho toàn bộ đợt nhập.
     *
     * @throws ValidationException when completing would exceed agency warehouse quota
     */
    public function complete(?User $user = null): void
    {
        // Validate agency quota before completing
        $this->validateAgencyQuota();

        $now = now();
        $userId = $user?->id ?? auth()->id();

        DB::transaction(function () use ($now, $userId) {
            $this->loadMissing(['items.asset']);

            foreach ($this->items as $item) {
                if (! $item->is_received) {
                    $item->update([
                        'is_received' => true,
                        'condition' => $item->condition ?: 'ok',
                        'received_at' => $now,
                        'received_by' => $userId,
                    ]);

                    if ($item->asset) {
                        $targetStatus = in_array($item->condition, ['fault', 'damaged'], true)
                            ? AssetStatus::Repairing
                            : AssetStatus::Ready;

                        $oldStatus = $item->asset->current_status;
                        $oldWhId = $item->asset->current_warehouse_id;

                        $item->asset->update([
                            'current_status' => $targetStatus,
                            'current_warehouse_id' => $this->warehouse_id,
                        ]);

                        AssetStatusLog::create([
                            'asset_id' => $item->asset->id,
                            'from_status' => $oldStatus,
                            'to_status' => $targetStatus,
                            'from_warehouse_id' => $oldWhId,
                            'to_warehouse_id' => $this->warehouse_id,
                            'source_type' => self::class,
                            'source_id' => $this->id,
                            'changed_by' => $userId,
                            'note' => 'Kết thúc nhận hàng: '.$this->code,
                            'created_at' => $now,
                        ]);
                    }
                }
            }

            $this->update([
                'status' => BatchStatus::Completed,
                'completed_at' => $now,
            ]);
        });
    }

    /**
     * Validate that completing this checkin batch will not exceed the destination agency warehouse quota.
     *
     * @throws ValidationException
     */
    protected function validateAgencyQuota(): void
    {
        if (! $this->warehouse_id) {
            return;
        }

        $agency = Agency::where('warehouse_id', $this->warehouse_id)->first();
        if (! $agency || (float) $agency->allocated_area_m2 <= 0) {
            return;
        }

        // Calculate incoming area from items not already in this warehouse
        $this->loadMissing(['items.asset.productLine']);

        $incomingArea = 0.0;
        foreach ($this->items as $item) {
            if ($item->is_received) {
                continue;
            }

            $asset = $item->asset;
            if (! $asset || $asset->current_warehouse_id === $this->warehouse_id) {
                continue;
            }

            $pl = $asset->productLine;
            if ($pl && $pl->module_width_mm > 0 && $pl->module_height_mm > 0) {
                $incomingArea += ($pl->module_width_mm / 1000.0) * ($pl->module_height_mm / 1000.0);
            } else {
                $incomingArea += 0.25;
            }
        }

        $incomingArea = round($incomingArea, 2);

        if ($incomingArea > 0 && $agency->wouldExceedQuota($incomingArea)) {
            $currentArea = $agency->current_inventory_area;
            $maxQuota = (float) $agency->allocated_area_m2;
            $exceededBy = round(($currentArea + $incomingArea) - $maxQuota, 2);

            throw ValidationException::withMessages([
                'warehouse_id' => "Đại lý \"{$agency->name}\" đã vượt hạn mức diện tích kho ({$maxQuota}m²). Hiện tại đang chứa {$currentArea}m², đợt nhập này thêm {$incomingArea}m² (vượt {$exceededBy}m²).",
            ]);
        }
    }
}
