<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReturnBatchItemResource;
use App\Http\Resources\Api\V1\ReturnBatchResource;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReturnBatchApiController extends Controller
{
    /**
     * List return batches with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReturnBatch::with([
            'checkoutBatch.warehouse',
            'checkoutBatch.order',
            'checkoutBatch.customer',
            'creator',
            'items.asset.productLine',
        ])->latest();

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->whereHas('checkoutBatch', fn ($cq) => $cq->where('warehouse_id', $warehouseId));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('checkoutBatch.order', fn ($oq) => $oq->where('order_no', 'like', "%{$search}%")->orWhere('event', 'like', "%{$search}%"))
                    ->orWhereHas('checkoutBatch.customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $batches = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => ReturnBatchResource::collection($batches),
            'pagination' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ],
        ]);
    }

    /**
     * Get single return batch details with all items and grading stats.
     */
    public function show(int $id): JsonResponse
    {
        $batch = ReturnBatch::with([
            'checkoutBatch.warehouse',
            'checkoutBatch.order',
            'checkoutBatch.customer',
            'creator',
            'items.asset.productLine',
            'items.receivedBy',
        ])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt thu hồi trả kho.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ReturnBatchResource($batch),
        ]);
    }

    /**
     * Scan and inspect/grade an asset into this return batch.
     */
    public function scan(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'grade' => ['nullable', Rule::enum(ReturnGrade::class)],
            'grade_note' => ['nullable', 'string', 'max:500'],
        ]);

        $batch = ReturnBatch::with(['items', 'checkoutBatch.order'])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt thu hồi trả kho.',
            ], 404);
        }

        if (in_array($batch->status, [ReturnBatchStatus::Completed])) {
            return response()->json([
                'success' => false,
                'message' => "Đợt thu hồi này đã ở trạng thái '{$batch->status->getLabel()}', không thể quét thêm.",
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

        $rawGrade = $request->input('grade', ReturnGrade::Normal->value);
        $grade = $rawGrade instanceof ReturnGrade ? $rawGrade : (ReturnGrade::tryFrom($rawGrade) ?? ReturnGrade::Normal);
        $gradeNote = $request->input('grade_note');

        return DB::transaction(function () use ($batch, $asset, $grade, $gradeNote) {
            $oldStatus = $asset->current_status;
            $warehouseId = $batch->checkoutBatch?->warehouse_id ?? $asset->current_warehouse_id;

            $existingItem = $batch->items()->where('asset_id', $asset->id)->first();

            if ($existingItem) {
                $existingItem->update([
                    'is_received' => true,
                    'grade' => $grade,
                    'grade_note' => $gradeNote,
                    'received_by' => Auth::id(),
                    'received_at' => now(),
                ]);
                $item = $existingItem;
            } else {
                $item = ReturnBatchItem::create([
                    'return_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                    'grade' => $grade,
                    'grade_note' => $gradeNote,
                    'is_received' => true,
                    'received_by' => Auth::id(),
                    'received_at' => now(),
                ]);
            }

            // Status transitions & Repair log if damaged
            if ($grade === ReturnGrade::Damaged) {
                $asset->update(['current_status' => AssetStatus::Repairing]);

                RepairLog::create([
                    'asset_id' => $asset->id,
                    'start_date' => now()->toDateString(),
                    'repair_note' => "Hỏng hóc sau sự kiện ghi nhận qua Mobile App (Đợt trả {$batch->code}): ".($gradeNote ?: 'Cần kiểm tra kỹ thuật & thay thế linh kiện'),
                    'result_status' => RepairResultStatus::Pending,
                    'created_by' => Auth::id(),
                ]);

                AssetStatusLog::create([
                    'asset_id' => $asset->id,
                    'from_status' => $oldStatus,
                    'to_status' => AssetStatus::Repairing,
                    'from_warehouse_id' => $asset->current_warehouse_id,
                    'to_warehouse_id' => $warehouseId,
                    'source_type' => ReturnBatch::class,
                    'source_id' => $batch->id,
                    'changed_by' => Auth::id(),
                    'note' => 'Thu hồi sau sự kiện (Hỏng hóc): '.($gradeNote ?: 'Cần bảo dưỡng'),
                    'created_at' => now(),
                ]);
            } else {
                $asset->update(['current_status' => AssetStatus::Ready]);

                AssetStatusLog::create([
                    'asset_id' => $asset->id,
                    'from_status' => $oldStatus,
                    'to_status' => AssetStatus::Ready,
                    'from_warehouse_id' => $asset->current_warehouse_id,
                    'to_warehouse_id' => $warehouseId,
                    'source_type' => ReturnBatch::class,
                    'source_id' => $batch->id,
                    'changed_by' => Auth::id(),
                    'note' => "Thu hồi sau sự kiện qua Mobile App ({$batch->code}) — Hoạt động tốt",
                    'created_at' => now(),
                ]);
            }

            // Update batch status to in_progress if currently pending
            if ($batch->status === ReturnBatchStatus::Pending) {
                $batch->update(['status' => ReturnBatchStatus::InProgress]);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã tiếp nhận kiểm đếm thiết bị: {$asset->serial_no} (".($grade === ReturnGrade::Normal ? 'Đạt chuẩn' : 'Ghi nhận lỗi: '.$grade->getLabel()).')',
                'data' => [
                    'item' => new ReturnBatchItemResource($item->load('asset.productLine')),
                    'grade' => $grade->value,
                    'total_scanned_count' => $batch->items()->where('is_received', true)->count(),
                ],
            ]);
        });
    }

    /**
     * Complete the entire return batch.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $batch = ReturnBatch::with(['items.asset', 'checkoutBatch.order'])->find($id);

        if (! $batch) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đợt thu hồi trả kho.',
            ], 404);
        }

        return DB::transaction(function () use ($batch) {
            $batch->update([
                'status' => ReturnBatchStatus::Completed,
                'completed_at' => now(),
            ]);

            if ($batch->checkoutBatch) {
                $batch->checkoutBatch->update(['status' => BatchStatus::Completed]);

                if ($batch->checkoutBatch->order) {
                    $batch->checkoutBatch->order->update(['status' => OrderStatus::Returned]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đợt thu hồi {$batch->code} đã hoàn tất kiểm đếm thành công.",
                'data' => new ReturnBatchResource($batch->fresh([
                    'checkoutBatch.warehouse',
                    'checkoutBatch.order',
                    'checkoutBatch.customer',
                    'items.asset',
                ])),
            ]);
        });
    }
}
