<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    /**
     * Đóng một phiếu bảo dưỡng / sửa chữa.
     *
     * Trong cùng một transaction, việc này phải đồng bộ cả ba nơi:
     *   1. RepairLog   — end_date, result_status (fixed|disposed), chi phí, ghi chú
     *   2. Asset       — current_status = Ready (sửa xong) hoặc Disposed (thanh lý)
     *   3. AssetStatusLog — vết chuyển trạng thái, source là chính RepairLog
     *
     * Không được chỉ cập nhật RepairLog rồi bỏ quên Asset (hoặc ngược lại) —
     * đó chính là lý do tách logic này thành một chỗ duy nhất.
     *
     * @param  array{end_date?: string|null, result_status?: string|null, repair_cost?: mixed, repair_note?: string|null}  $data
     */
    public function complete(RepairLog $repairLog, array $data, ?User $user = null): void
    {
        DB::transaction(function () use ($repairLog, $data, $user): void {
            $asset = $repairLog->asset;

            if (! $asset) {
                return;
            }

            $isDisposed = ($data['result_status'] ?? RepairResultStatus::Fixed->value) === RepairResultStatus::Disposed->value;

            $newAssetStatus = $isDisposed ? AssetStatus::Disposed : AssetStatus::Ready;
            $newResultStatus = $isDisposed ? RepairResultStatus::Disposed : RepairResultStatus::Fixed;
            $fromStatus = $asset->current_status;

            // Form dùng mask $money($input) nên giá trị có thể còn dấu phẩy phân
            // cách hàng nghìn (VD "500,000") — bỏ dấu phẩy trước khi ép kiểu số.
            $cost = filled($data['repair_cost'] ?? null)
                ? (float) str_replace(',', '', (string) $data['repair_cost'])
                : null;

            $note = $data['repair_note'] ?? null;

            $repairLog->update([
                'end_date' => $data['end_date'] ?? now()->toDateString(),
                'result_status' => $newResultStatus,
                'repair_cost' => $cost ?? $repairLog->repair_cost,
                'repair_note' => filled($note)
                    ? ($repairLog->repair_note ? $repairLog->repair_note."\n[Hoàn thành]: ".$note : $note)
                    : $repairLog->repair_note,
            ]);

            $asset->update(['current_status' => $newAssetStatus]);

            AssetStatusLog::create([
                'asset_id' => $asset->id,
                'from_status' => $fromStatus,
                'to_status' => $newAssetStatus,
                'from_warehouse_id' => $asset->current_warehouse_id,
                'to_warehouse_id' => $asset->current_warehouse_id,
                'source_type' => RepairLog::class,
                'source_id' => $repairLog->id,
                'changed_by' => $user?->id ?? Auth::id(),
                'note' => 'Hoàn thành bảo trì/sửa chữa: '.($note ?: ($isDisposed ? 'Thanh lý' : 'Đã sửa xong')),
                'created_at' => now(),
            ]);
        });
    }
}
