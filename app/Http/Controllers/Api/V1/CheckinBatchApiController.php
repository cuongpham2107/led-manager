<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CheckinBatchItemResource;
use App\Http\Resources\Api\V1\CheckinBatchResource;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckinBatchApiController extends Controller
{
    /**
     * List check-in batches with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CheckinBatch::with(['warehouse', 'productLine', 'creator', 'items.asset'])
            ->latest();

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($batchType = $request->input('batch_type')) {
            $query->where('batch_type', $batchType);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('production_note', 'like', "%{$search}%");
            });
        }

        $batches = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => CheckinBatchResource::collection($batches),
            'pagination' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ],
        ]);
    }

    /**
     * Get a single check-in batch with all items.
     */
    public function show(int $id): JsonResponse
    {
        $batch = CheckinBatch::with([
            'warehouse',
            'productLine',
            'creator',
            'items.asset.productLine',
            'items.receivedByUser',
        ])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt nhập kho.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CheckinBatchResource($batch),
        ]);
    }

    /**
     * Scan an asset QR or Serial to mark it received in this check-in batch.
     */
    public function scan(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'condition' => ['nullable', 'in:ok,fault'],
            'condition_note' => ['nullable', 'string', 'max:500'],
        ]);

        $batch = CheckinBatch::with(['items'])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt nhập kho.',
            ], 404);
        }

        if (in_array($batch->status, [BatchStatus::Cancelled, BatchStatus::Completed])) {
            return response()->json([
                'success' => false,
                'message' => "Đợt nhập kho này đã ở trạng thái '{$batch->status->getLabel()}', không thể quét thêm.",
            ], 422);
        }

        $code = trim($request->input('code'));

        // Strip URL wrapper if QR encodes a full URL (e.g. https://domain/q/LED-001)
        if (preg_match('#/q/([^/?]+)#i', $code, $matches)) {
            $code = $matches[1];
        }

        $asset = Asset::with('productLine')
            ->where('serial_no', $code)
            ->orWhere('qr_code', $code)
            ->orWhere('id', is_numeric($code) ? (int) $code : 0)
            ->first();

        if (! $asset) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy thiết bị nào khớp với mã: '{$code}'.",
            ], 404);
        }

        // Idempotency: already received in this batch
        $existingItem = $batch->items()->where('asset_id', $asset->id)->first();
        if ($existingItem && $existingItem->is_received) {
            return response()->json([
                'success' => false,
                'message' => "Thiết bị {$asset->serial_no} đã được nhập trong đợt này rồi.",
                'data' => [
                    'item' => new CheckinBatchItemResource($existingItem->load('asset.productLine', 'receivedByUser')),
                ],
            ], 422);
        }

        return DB::transaction(function () use ($batch, $asset, $existingItem, $request) {
            $oldStatus = $asset->current_status;
            $oldWarehouseId = $asset->current_warehouse_id;
            $now = now();
            $condition = $request->input('condition', 'ok');
            $conditionNote = $request->input('condition_note');

            if ($existingItem) {
                $existingItem->update([
                    'condition' => $condition,
                    'condition_note' => $conditionNote,
                    'is_received' => true,
                    'received_by' => Auth::id(),
                    'received_at' => $now,
                ]);
                $item = $existingItem;
            } else {
                $item = CheckinBatchItem::create([
                    'checkin_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                    'condition' => $condition,
                    'condition_note' => $conditionNote,
                    'is_received' => true,
                    'received_by' => Auth::id(),
                    'received_at' => $now,
                ]);
            }

            // Move asset to Ready in the target warehouse
            $asset->update([
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $batch->warehouse_id,
            ]);

            // Move batch in_progress
            if ($batch->status === BatchStatus::Pending) {
                $batch->update(['status' => BatchStatus::InProgress]);
            }

            AssetStatusLog::create([
                'asset_id' => $asset->id,
                'from_status' => $oldStatus,
                'to_status' => AssetStatus::Ready,
                'from_warehouse_id' => $oldWarehouseId,
                'to_warehouse_id' => $batch->warehouse_id,
                'source_type' => CheckinBatch::class,
                'source_id' => $batch->id,
                'changed_by' => Auth::id(),
                'note' => "Quét nhập kho di động qua App: {$batch->code}",
                'created_at' => $now,
            ]);

            // Auto-complete when all expected items are received
            $scannedCount = $batch->items()->where('is_received', true)->count();
            $targetCount = max((int) $batch->quantity, $batch->items()->count());

            if ($targetCount > 0 && $scannedCount >= $targetCount && $batch->status !== BatchStatus::Completed) {
                $batch->update([
                    'status' => BatchStatus::Completed,
                    'completed_at' => $now,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã quét nhập kho thành công thiết bị: {$asset->serial_no}",
                'data' => [
                    'item' => new CheckinBatchItemResource($item->load('asset.productLine', 'receivedByUser')),
                    'scanned_count' => $scannedCount,
                    'target_items_count' => $targetCount,
                    'progress_percent' => $targetCount > 0 ? min(100, (int) round(($scannedCount / $targetCount) * 100)) : 0,
                ],
            ]);
        });
    }

    /**
     * Mark the batch as fully received and completed.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $batch = CheckinBatch::with(['items.asset'])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt nhập kho.',
            ], 404);
        }

        if ($batch->status === BatchStatus::Completed) {
            return response()->json([
                'success' => false,
                'message' => 'Đợt nhập kho đã ở trạng thái hoàn thành.',
            ], 422);
        }

        if ($batch->status === BatchStatus::Cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Đợt nhập kho đã bị hủy.',
            ], 422);
        }

        $now = now();

        return DB::transaction(function () use ($batch, $now) {
            // Đảm bảo tất cả items trong batch đều được nhận
            $batch->items()->where('is_received', false)->update([
                'is_received' => true,
                'received_by' => Auth::id(),
                'received_at' => $now,
            ]);

            // Đồng bộ tất cả asset trong batch về Ready + kho đích
            $batch->items->each(function ($item) use ($batch, $now) {
                $asset = $item->asset;

                if (! $asset) {
                    return;
                }

                $oldStatus = $asset->current_status;
                $oldWarehouseId = $asset->current_warehouse_id;

                if ($asset->current_status !== AssetStatus::Ready || $asset->current_warehouse_id !== $batch->warehouse_id) {
                    $asset->update([
                        'current_status' => AssetStatus::Ready,
                        'current_warehouse_id' => $batch->warehouse_id,
                    ]);

                    AssetStatusLog::create([
                        'asset_id' => $asset->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::Ready,
                        'from_warehouse_id' => $oldWarehouseId,
                        'to_warehouse_id' => $batch->warehouse_id,
                        'source_type' => CheckinBatch::class,
                        'source_id' => $batch->id,
                        'changed_by' => Auth::id(),
                        'note' => 'Hoàn tất đợt nhập kho: '.$batch->code,
                        'created_at' => $now,
                    ]);
                }
            });

            $batch->update([
                'status' => BatchStatus::Completed,
                'completed_at' => $now,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Đợt nhập kho {$batch->code} đã hoàn tất. Tất cả thiết bị đã được chuyển về trạng thái Sẵn sàng.",
                'data' => new CheckinBatchResource($batch->fresh(['warehouse', 'productLine', 'items.asset'])),
            ]);
        });
    }
}
