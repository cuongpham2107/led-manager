<?php

namespace App\Filament\Resources\CheckoutBatches\Pages;

use App\Enums\BatchStatus;
use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Services\CodeGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateCheckoutBatch extends CreateRecord
{
    protected static string $resource = CheckoutBatchResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): CheckoutBatch {
            $selectedAssets = $data['selected_assets'] ?? [];
            unset($data['selected_assets']);
            unset($data['product_line_id']);

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
    }
}
