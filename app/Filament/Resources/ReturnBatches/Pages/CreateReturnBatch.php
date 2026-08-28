<?php

namespace App\Filament\Resources\ReturnBatches\Pages;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnGrade;
use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;

class CreateReturnBatch extends CreateRecord
{
    protected static string $resource = ReturnBatchResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function afterCreate(): void
    {
        /** @var ReturnBatch $record */
        $record = $this->record;

        $record->loadMissing('items.asset', 'checkoutBatch.order');

        foreach ($record->items as $item) {
            if ($item->is_received && $asset = $item->asset) {
                $oldStatus = $asset->current_status;

                if ($item->grade === ReturnGrade::Damaged) {
                    $asset->update(['current_status' => AssetStatus::Repairing]);

                    RepairLog::create([
                        'asset_id' => $asset->id,
                        'start_date' => now()->toDateString(),
                        'repair_note' => 'Hỏng hóc phát hiện khi nhập trả kho: '.($item->grade_note ?: 'Cần kiểm tra kỹ thuật'),
                        'result_status' => RepairResultStatus::Pending,
                        'created_by' => Auth::id(),
                    ]);

                    AssetStatusLog::create([
                        'asset_id' => $asset->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::Repairing,
                        'from_warehouse_id' => $asset->current_warehouse_id,
                        'to_warehouse_id' => $asset->current_warehouse_id,
                        'source_type' => ReturnBatch::class,
                        'source_id' => $record->id,
                        'changed_by' => Auth::id(),
                        'note' => 'Thu hồi sau sự kiện (Hỏng hóc): '.($item->grade_note ?: 'Cần bảo dưỡng'),
                        'created_at' => now(),
                    ]);
                } else {
                    $asset->update(['current_status' => AssetStatus::Ready]);

                    AssetStatusLog::create([
                        'asset_id' => $asset->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::Ready,
                        'from_warehouse_id' => $asset->current_warehouse_id,
                        'to_warehouse_id' => $asset->current_warehouse_id,
                        'source_type' => ReturnBatch::class,
                        'source_id' => $record->id,
                        'changed_by' => Auth::id(),
                        'note' => 'Thu hồi sau sự kiện — Hoạt động tốt',
                        'created_at' => now(),
                    ]);
                }
            }
        }

        if ($record->checkoutBatch) {
            $record->checkoutBatch->update(['status' => BatchStatus::Completed]);
            if ($record->checkoutBatch->order) {
                $record->checkoutBatch->order->update(['status' => OrderStatus::Returned]);
            }
        }
    }
}
