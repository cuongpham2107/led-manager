<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

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
            'status' => ReturnBatchStatus::class,
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

    /**
     * Hoàn tất nhận hàng cho toàn bộ đợt nhập trả.
     */
    public function complete(?User $user = null): void
    {
        $now = now();
        $userId = $user?->id ?? auth()->id();

        DB::transaction(function () use ($now, $userId) {
            $this->loadMissing(['items.asset', 'checkoutBatch.order']);

            $checkoutBatch = $this->checkoutBatch;
            $order = $checkoutBatch?->order;
            $targetWarehouseId = $checkoutBatch?->warehouse_id;

            foreach ($this->items as $item) {
                $asset = $item->asset;
                if (! $asset) {
                    continue;
                }

                $oldStatus = $asset->current_status;
                $oldWarehouseId = $asset->current_warehouse_id;

                if (! $item->is_received) {
                    $asset->update(['current_status' => AssetStatus::Missing]);

                    AssetStatusLog::create([
                        'asset_id' => $asset->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::Missing,
                        'from_warehouse_id' => $oldWarehouseId,
                        'to_warehouse_id' => $targetWarehouseId ?? $oldWarehouseId,
                        'source_type' => self::class,
                        'source_id' => $this->id,
                        'changed_by' => $userId,
                        'note' => $order
                            ? "Thiết bị KHÔNG trả về sau sự kiện '{$order->event}' (Đơn hàng {$order->order_no}). Chuyển sang trạng thái Mất / Chưa trả về."
                            : 'Thiết bị KHÔNG trả về khi thu hồi đợt xuất. Chuyển sang trạng thái Mất / Chưa trả về.',
                        'created_at' => $now,
                    ]);

                    continue;
                }

                if ($item->grade === ReturnGrade::Damaged) {
                    $asset->update([
                        'current_status' => AssetStatus::Repairing,
                        'current_warehouse_id' => $targetWarehouseId ?? $oldWarehouseId,
                    ]);

                    RepairLog::create([
                        'asset_id' => $asset->id,
                        'start_date' => $now->toDateString(),
                        'repair_note' => $order
                            ? "Hỏng hóc sau sự kiện '{$order->event}' (Đơn hàng {$order->order_no}): ".($item->grade_note ?: 'Cần kiểm tra kỹ thuật')
                            : 'Hỏng hóc khi thu hồi đợt xuất: '.($item->grade_note ?: 'Cần kiểm tra kỹ thuật'),
                        'result_status' => RepairResultStatus::Pending,
                        'created_by' => $userId,
                    ]);

                    AssetStatusLog::create([
                        'asset_id' => $asset->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::Repairing,
                        'from_warehouse_id' => $oldWarehouseId,
                        'to_warehouse_id' => $targetWarehouseId ?? $oldWarehouseId,
                        'source_type' => self::class,
                        'source_id' => $this->id,
                        'changed_by' => $userId,
                        'note' => 'Thu hồi sau sự kiện (Hỏng hóc): '.($item->grade_note ?: 'Cần bảo dưỡng'),
                        'created_at' => $now,
                    ]);
                } else {
                    $asset->update([
                        'current_status' => AssetStatus::Ready,
                        'current_warehouse_id' => $targetWarehouseId ?? $oldWarehouseId,
                    ]);

                    AssetStatusLog::create([
                        'asset_id' => $asset->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::Ready,
                        'from_warehouse_id' => $oldWarehouseId,
                        'to_warehouse_id' => $targetWarehouseId ?? $oldWarehouseId,
                        'source_type' => self::class,
                        'source_id' => $this->id,
                        'changed_by' => $userId,
                        'note' => $order
                            ? "Thu hồi sau sự kiện '{$order->event}' — Hoạt động tốt"
                            : 'Thu hồi sau đợt xuất kho — Hoạt động tốt',
                        'created_at' => $now,
                    ]);
                }
            }

            if ($checkoutBatch) {
                $checkoutBatch->update(['status' => BatchStatus::Completed]);
            }

            if ($order && $order->checkoutBatches()->where('status', '!=', BatchStatus::Completed)->doesntExist()) {
                $order->update(['status' => OrderStatus::Returned]);
            }

            $this->update([
                'status' => ReturnBatchStatus::Completed,
                'completed_at' => $now,
            ]);
        });
    }
}
