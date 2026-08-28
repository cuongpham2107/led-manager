<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CheckoutBatchItemResource;
use App\Http\Resources\Api\V1\CheckoutBatchResource;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutBatchApiController extends Controller
{
    /**
     * List checkout batches with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CheckoutBatch::with(['order', 'customer', 'warehouse', 'deviceType', 'creator', 'items.asset.productLine'])
            ->latest();

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($oq) => $oq->where('order_no', 'like', "%{$search}%")->orWhere('event', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $batches = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => CheckoutBatchResource::collection($batches),
            'pagination' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ],
        ]);
    }

    /**
     * Get single checkout batch details with all items.
     */
    public function show(int $id): JsonResponse
    {
        $batch = CheckoutBatch::with([
            'order',
            'customer',
            'warehouse',
            'deviceType',
            'creator',
            'items.asset.productLine',
            'items.dispatchedBy',
        ])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt xuất kho.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CheckoutBatchResource($batch),
        ]);
    }

    /**
     * Scan an asset QR or Serial Number to dispatch into this checkout batch.
     */
    public function scan(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $batch = CheckoutBatch::with(['items', 'order'])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt xuất kho.',
            ], 404);
        }

        if (in_array($batch->status, [BatchStatus::Cancelled, BatchStatus::Completed])) {
            return response()->json([
                'success' => false,
                'message' => "Đợt xuất kho này đã ở trạng thái '{$batch->status->getLabel()}', không thể quét thêm.",
            ], 422);
        }

        $code = trim($request->input('code'));
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

        if (in_array($asset->current_status, [AssetStatus::Repairing, AssetStatus::Disposed])) {
            return response()->json([
                'success' => false,
                'message' => "Thiết bị {$asset->serial_no} đang ở trạng thái '{$asset->current_status->getLabel()}', không thể xuất kho đi sự kiện.",
            ], 422);
        }

        // Check if already scanned in this batch
        $existingItem = $batch->items()->where('asset_id', $asset->id)->first();
        if ($existingItem && $existingItem->is_dispatched) {
            return response()->json([
                'success' => false,
                'message' => "Thiết bị {$asset->serial_no} đã được quét trong đợt xuất kho này rồi.",
                'data' => [
                    'item' => new CheckoutBatchItemResource($existingItem->load('asset.productLine')),
                ],
            ], 422);
        }

        return DB::transaction(function () use ($batch, $asset, $existingItem) {
            $oldStatus = $asset->current_status;

            if ($existingItem) {
                $existingItem->update([
                    'is_dispatched' => true,
                    'dispatched_by' => Auth::id(),
                    'dispatched_at' => now(),
                ]);
                $item = $existingItem;
            } else {
                $item = CheckoutBatchItem::create([
                    'checkout_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                    'is_dispatched' => true,
                    'dispatched_by' => Auth::id(),
                    'dispatched_at' => now(),
                ]);
            }

            // Update asset status to InTransit
            $asset->update(['current_status' => AssetStatus::InTransit]);

            // Update batch status to in_progress if currently pending
            if ($batch->status === BatchStatus::Pending) {
                $batch->update(['status' => BatchStatus::InProgress]);
            }

            // Create status log
            AssetStatusLog::create([
                'asset_id' => $asset->id,
                'from_status' => $oldStatus,
                'to_status' => AssetStatus::InTransit,
                'from_warehouse_id' => $asset->current_warehouse_id,
                'to_warehouse_id' => $batch->warehouse_id,
                'source_type' => CheckoutBatch::class,
                'source_id' => $batch->id,
                'changed_by' => Auth::id(),
                'note' => "Quét xuất kho di động qua App: {$batch->code}",
                'created_at' => now(),
            ]);

            $scannedCount = $batch->items()->where('is_dispatched', true)->count();
            $cabinetsPerM2 = 4;
            $targetCount = max((int) ceil(((float) $batch->required_area_m2) * $cabinetsPerM2), $batch->items()->count());

            return response()->json([
                'success' => true,
                'message' => "Đã quét thành công thiết bị: {$asset->serial_no}",
                'data' => [
                    'item' => new CheckoutBatchItemResource($item->load('asset.productLine')),
                    'scanned_count' => $scannedCount,
                    'target_cabinets_count' => $targetCount,
                    'progress_percent' => $targetCount > 0 ? min(100, (int) round(($scannedCount / $targetCount) * 100)) : 100,
                ],
            ]);
        });
    }

    /**
     * Complete and dispatch the entire checkout batch.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $batch = CheckoutBatch::with(['items.asset', 'order'])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt xuất kho.',
            ], 404);
        }

        return DB::transaction(function () use ($batch) {
            $batch->update([
                'status' => BatchStatus::Dispatched,
                'dispatched_at' => now(),
            ]);

            // Transition all assets in this batch to InEvent
            foreach ($batch->items as $item) {
                if ($item->asset) {
                    $oldStatus = $item->asset->current_status;
                    $item->asset->update(['current_status' => AssetStatus::InEvent]);

                    AssetStatusLog::create([
                        'asset_id' => $item->asset->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::InEvent,
                        'from_warehouse_id' => $item->asset->current_warehouse_id,
                        'to_warehouse_id' => $batch->warehouse_id,
                        'source_type' => CheckoutBatch::class,
                        'source_id' => $batch->id,
                        'changed_by' => Auth::id(),
                        'note' => "Đợt xuất {$batch->code} hoàn tất đi sự kiện",
                        'created_at' => now(),
                    ]);
                }
            }

            if ($batch->order) {
                $batch->order->update(['status' => OrderStatus::Dispatched]);
            }

            return response()->json([
                'success' => true,
                'message' => "Đợt xuất kho {$batch->code} đã được xuất kho đi sự kiện thành công.",
                'data' => new CheckoutBatchResource($batch->fresh(['order', 'customer', 'warehouse', 'items.asset'])),
            ]);
        });
    }
}
