<?php

namespace App\Filament\Resources\CheckoutBatches\Actions;

use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use App\Models\CheckoutBatch;
use Filament\Actions\Action;

class ViewReturnBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'view_return_batch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Xem Đợt Trả Kho')
            ->icon('heroicon-o-document-magnifying-glass')
            ->color('info')
            ->visible(fn (CheckoutBatch $record): bool => $record->returnBatches->isNotEmpty())
            ->url(fn (CheckoutBatch $record): ?string => ($returnBatch = $record->returnBatches->first()) ? ReturnBatchResource::getUrl('edit', ['record' => $returnBatch]) : null);
    }
}
