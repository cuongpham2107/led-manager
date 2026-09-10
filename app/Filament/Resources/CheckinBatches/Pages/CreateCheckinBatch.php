<?php

namespace App\Filament\Resources\CheckinBatches\Pages;

use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Filament\Resources\CheckinBatches\Actions\ImportCheckinBatchItemsAction;
use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateCheckinBatch extends CreateRecord
{
    protected static string $resource = CheckinBatchResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            ImportCheckinBatchItemsAction::make(),
        ];
    }

    public function mountImport(): void
    {
        $this->mountAction('import_checkin_batch_items');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): CheckinBatch {
            $selectedAssets = $data['selected_assets'] ?? [];
            unset($data['selected_assets']);

            $assetIds = collect($selectedAssets)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            $data['status'] = BatchStatus::Pending;
            $data['batch_type'] = CheckinBatchType::Transfer;
            $data['created_by'] = Auth::id();
            // Mục tiêu = số thiết bị đã chọn, để tiến độ khớp giữa web và app.
            $data['quantity'] = $assetIds->count();

            $batch = CheckinBatch::create($data);

            foreach ($assetIds as $assetId) {
                // Thêm vào đợt ở dạng CHỜ NHẬN để quét nhận trên app di động.
                CheckinBatchItem::create([
                    'checkin_batch_id' => $batch->id,
                    'asset_id' => $assetId,
                    'condition' => 'ok',
                    'is_received' => false,
                    'received_at' => null,
                    'received_by' => null,
                ]);
            }

            if ($assetIds->isNotEmpty()) {
                Asset::whereIn('id', $assetIds)->update([
                    'current_warehouse_id' => $data['warehouse_id'],
                ]);
            }

            return $batch;
        });
    }
}
