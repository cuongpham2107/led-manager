<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use App\Models\Order;
use Filament\Actions\Action;

class ViewCheckoutBatchAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'view_checkout_batch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Xem Đợt Xuất Kho')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('warning')
            ->visible(fn (Order $record): bool => $record->checkoutBatches->isNotEmpty())
            ->url(fn (Order $record): ?string => ($batch = $record->checkoutBatches->first()) ? CheckoutBatchResource::getUrl('edit', ['record' => $batch]) : null);
    }
}
