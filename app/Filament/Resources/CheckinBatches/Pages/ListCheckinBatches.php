<?php

namespace App\Filament\Resources\CheckinBatches\Pages;

use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Filament\Resources\CheckinBatches\Actions\CreateProductionBatchAction;
use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListCheckinBatches extends ListRecords
{
    protected static string $resource = CheckinBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tạo đợt nhập')
                ->icon('heroicon-o-plus')
                ->modalHeading('Tạo đợt nhập')
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitActionLabel('Lưu')
                ->modalCancelActionLabel('Hủy')
                ->using(function (array $data): CheckinBatch {
                    return DB::transaction(function () use ($data) {
                        $selectedAssets = $data['selected_assets'] ?? [];
                        unset($data['selected_assets']);

                        $data['status'] = BatchStatus::Pending;
                        $data['batch_type'] = CheckinBatchType::Transfer;
                        $data['created_by'] = Auth::id();

                        $batch = CheckinBatch::create($data);

                        $assetIds = collect($selectedAssets)->map(fn ($id) => (int) $id)->filter()->values();
                        foreach ($assetIds as $assetId) {
                            CheckinBatchItem::create([
                                'checkin_batch_id' => $batch->id,
                                'asset_id' => $assetId,
                                'condition' => 'ok',
                                'is_received' => true,
                                'received_at' => now(),
                                'received_by' => Auth::id(),
                            ]);
                        }

                        if ($assetIds->isNotEmpty()) {
                            Asset::whereIn('id', $assetIds)->update([
                                'current_warehouse_id' => $data['warehouse_id'],
                            ]);
                        }

                        return $batch;
                    });
                }),
            // CreateProductionBatchAction::make(),
        ];
    }
}
