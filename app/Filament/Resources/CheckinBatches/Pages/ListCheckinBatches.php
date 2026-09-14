<?php

namespace App\Filament\Resources\CheckinBatches\Pages;

use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use App\Filament\Resources\CheckinBatches\Widgets\CheckinBatchStatsWidget;
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

    protected function getHeaderWidgets(): array
    {
        return [
            CheckinBatchStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tạo đợt nhập')
                ->icon('heroicon-o-plus')
                ->modalHeading('Tạo đợt nhập kho mới')
                ->modalWidth(Width::FourExtraLarge)
                ->using(function (array $data): CheckinBatch {
                    return DB::transaction(function () use ($data): CheckinBatch {
                        $selectedAssets = $data['selected_assets'] ?? [];
                        unset($data['selected_assets']);

                        $assetIds = collect($selectedAssets)
                            ->map(fn ($id) => (int) $id)
                            ->filter()
                            ->values();

                        $data['status'] = BatchStatus::Pending;
                        $data['batch_type'] = $data['batch_type'] ?? CheckinBatchType::Purchase;
                        $data['created_by'] = Auth::id();
                        $data['quantity'] = $assetIds->count();

                        $batch = CheckinBatch::create($data);

                        foreach ($assetIds as $assetId) {
                            CheckinBatchItem::create([
                                'checkin_batch_id' => $batch->id,
                                'asset_id' => $assetId,
                                'condition' => null,
                                'is_received' => false,
                                'received_at' => null,
                                'received_by' => null,
                            ]);
                        }

                        return $batch;
                    });
                }),
        ];
    }
}
