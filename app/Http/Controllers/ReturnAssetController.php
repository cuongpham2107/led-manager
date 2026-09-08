<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnAssetController extends Controller
{
    /**
     * Nhận hàng / Kiểm đếm cho 1 thiết bị cụ thể trong đợt nhập trả.
     */
    public function receiveItem(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => ['required', 'integer', 'exists:return_batches,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'condition' => ['required', 'string', 'in:normal,damaged,ok,fault'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $batch = ReturnBatch::with('checkoutBatch')->findOrFail($request->input('batch_id'));
        $asset = Asset::with('productLine')->findOrFail($request->input('asset_id'));

        $conditionInput = $request->input('condition');
        $isDamaged = in_array($conditionInput, ['damaged', 'fault'], true);
        $grade = $isDamaged ? ReturnGrade::Damaged : ReturnGrade::Normal;
        $targetAssetStatus = $isDamaged ? AssetStatus::Repairing : AssetStatus::Ready;
        $targetWarehouseId = $batch->checkoutBatch?->warehouse_id ?? $asset->current_warehouse_id;

        $now = now();
        $user = auth()->user();

        return DB::transaction(function () use ($batch, $asset, $grade, $isDamaged, $targetAssetStatus, $targetWarehouseId, $now, $user, $request) {
            $item = ReturnBatchItem::firstOrNew([
                'return_batch_id' => $batch->id,
                'asset_id' => $asset->id,
            ]);

            $item->is_received = true;
            $item->grade = $grade;
            $item->grade_note = $request->input('note');
            $item->received_at = $now;
            $item->received_by = $user?->id;
            $item->save();

            $oldStatus = $asset->current_status;
            $oldWarehouseId = $asset->current_warehouse_id;

            $asset->update([
                'current_status' => $targetAssetStatus,
                'current_warehouse_id' => $targetWarehouseId,
            ]);

            if ($isDamaged) {
                RepairLog::create([
                    'asset_id' => $asset->id,
                    'start_date' => $now->toDateString(),
                    'repair_note' => 'Hỏng hóc khi thu hồi đợt trả ['.$batch->code.']: '.($item->grade_note ?: 'Cần kiểm tra kỹ thuật'),
                    'result_status' => RepairResultStatus::Pending,
                    'created_by' => $user?->id,
                ]);
            }

            AssetStatusLog::create([
                'asset_id' => $asset->id,
                'from_status' => $oldStatus,
                'to_status' => $targetAssetStatus,
                'from_warehouse_id' => $oldWarehouseId,
                'to_warehouse_id' => $targetWarehouseId,
                'source_type' => ReturnBatch::class,
                'source_id' => $batch->id,
                'changed_by' => $user?->id,
                'note' => 'Nhận trả thiết bị đợt: '.$batch->code.($isDamaged ? ' (Hỏng hóc)' : ' (Bình thường)'),
                'created_at' => $now,
            ]);

            if ($batch->status === ReturnBatchStatus::Pending) {
                $batch->update(['status' => ReturnBatchStatus::InProgress]);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã nhận hàng hoàn trả thiết bị {$asset->serial_no}",
                'item' => [
                    'id' => (int) $asset->id,
                    'item_id' => (int) $item->id,
                    'serial_no' => (string) $asset->serial_no,
                    'name' => (string) ($asset->productLine?->name ?? 'LED'),
                    'is_received' => true,
                    'condition' => $isDamaged ? 'damaged' : 'normal',
                    'condition_raw' => $grade->value,
                    'grade_note' => $item->grade_note,
                    'received_at' => $now->format('d/m/Y H:i'),
                ],
            ]);
        });
    }

    /**
     * Hoàn tất toàn bộ đợt nhận trả.
     */
    public function completeBatch(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => ['required', 'integer', 'exists:return_batches,id'],
        ]);

        $batch = ReturnBatch::findOrFail($request->input('batch_id'));
        $batch->complete(auth()->user());

        return response()->json([
            'success' => true,
            'message' => "Đợt nhập trả {$batch->code} đã kết thúc nhận hàng hoàn tất!",
        ]);
    }
}
