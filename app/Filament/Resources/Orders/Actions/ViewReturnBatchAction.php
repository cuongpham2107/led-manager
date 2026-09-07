<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use App\Models\Order;
use App\Models\ReturnBatch;
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
            ->authorize('ViewReturnBatch:Order')
            ->label('Xem Đợt Trả Kho')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('primary')
            ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Returned, OrderStatus::Completed]) && ($record->returnBatches()->exists() || ReturnBatch::whereIn('checkout_batch_id', $record->checkoutBatches()->select('id'))->exists()))
            ->url(fn (Order $record): ?string => ($returnBatch = $record->returnBatches()->latest('id')->first() ?? ReturnBatch::whereIn('checkout_batch_id', $record->checkoutBatches()->select('id'))->latest('id')->first()) ? ReturnBatchResource::getUrl('edit', ['record' => $returnBatch]) : null);
    }
}
