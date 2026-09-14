<?php

namespace App\Filament\Resources\CheckoutBatches\Pages;

use App\Enums\BatchStatus;
use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Services\CodeGeneratorService;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
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
                ->createAnother(false)
                ->before(function (CreateAction $action, array $data) {
                    $selectedAssets = $data['selected_assets'] ?? [];
                    $assetIds = collect($selectedAssets)->map(fn ($id) => (int) $id)->filter()->values();
                    if ($assetIds->isEmpty()) {
                        Notification::make()
                            ->title('Không đủ điều kiện để xuất kho')
                            ->body('Vui lòng chọn ít nhất một thiết bị xuất kho.')
                            ->danger()
                            ->send();
                        $action->halt();
                    }

                    $requiredArea = (float) ($data['required_area_m2'] ?? 0);
                    if ($requiredArea > 0) {
                        $assets = Asset::with('productLine')->whereIn('id', $assetIds)->get();
                        $totalArea = (float) $assets->sum(fn (Asset $a) => $a->area_m2);
                        if (round($totalArea, 2) < round($requiredArea, 2)) {
                            Notification::make()
                                ->title('Không đủ điều kiện để xuất kho')
                                ->body('Kho chỉ có '.number_format($totalArea, 2).' m² khả dụng, không đủ '.number_format($requiredArea, 2).' m² theo yêu cầu.')
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    }
                })
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
                                'is_dispatched' => false,
                                'dispatched_by' => null,
                                'dispatched_at' => null,
                                'note' => $data['note'] ?? null,
                            ]);
                        }

                        return $batch;
                    });
                }),
        ];
    }
}
