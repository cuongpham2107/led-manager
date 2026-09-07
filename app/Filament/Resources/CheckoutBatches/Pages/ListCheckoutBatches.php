<?php

namespace App\Filament\Resources\CheckoutBatches\Pages;

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Services\CodeGeneratorService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListCheckoutBatches extends ListRecords
{
    protected static string $resource = CheckoutBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Thêm đợt xuất')
                ->icon('heroicon-o-plus')
                ->modalHeading('Thêm đợt xuất')
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitActionLabel('Lưu đợt xuất')
                ->modalCancelActionLabel('Hủy')
                ->using(function (array $data): CheckoutBatch {
                    return DB::transaction(function () use ($data) {
                        $selectedAssets = $data['selected_assets'] ?? [];
                        unset($data['selected_assets']);
                        unset($data['product_line_id']);

                        // Clean up code if it contains ' · Hệ thống tự sinh'
                        $code = trim(explode('·', $data['code'] ?? '')[0]);
                        if (empty($code)) {
                            $code = CodeGeneratorService::generate('OUT', 'checkout_batches');
                        }
                        $data['code'] = $code;
                        $data['status'] = BatchStatus::Pending;
                        $data['created_by'] = Auth::id();

                        $batch = CheckoutBatch::create($data);

                        $assetIds = collect($selectedAssets)->map(fn ($id) => (int) $id)->filter()->values();
                        foreach ($assetIds as $assetId) {
                            CheckoutBatchItem::create([
                                'checkout_batch_id' => $batch->id,
                                'asset_id' => $assetId,
                                'is_dispatched' => true,
                                'dispatched_by' => Auth::id(),
                                'dispatched_at' => now(),
                                'note' => $data['note'] ?? null,
                            ]);

                            $asset = Asset::find($assetId);
                            if ($asset) {
                                $oldStatus = $asset->current_status;
                                $asset->update(['current_status' => AssetStatus::InTransit]);

                                AssetStatusLog::create([
                                    'asset_id' => $asset->id,
                                    'from_status' => $oldStatus,
                                    'to_status' => AssetStatus::InTransit,
                                    'from_warehouse_id' => $asset->current_warehouse_id,
                                    'to_warehouse_id' => $batch->warehouse_id,
                                    'source_type' => CheckoutBatch::class,
                                    'source_id' => $batch->id,
                                    'changed_by' => Auth::id(),
                                    'note' => "Tạo xuất kho: Đợt {$code}",
                                    'created_at' => now(),
                                ]);
                            }
                        }

                        if ($assetIds->isNotEmpty()) {
                            $batch->update(['status' => BatchStatus::InProgress]);
                        }

                        return $batch;
                    });
                }),
        ];
    }
}
