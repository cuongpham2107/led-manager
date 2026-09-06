<?php

namespace App\Filament\Resources\Quotations\Actions;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Quotation;
use Filament\Actions\Action;

class ViewOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'view_order';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('ViewOrder:Quotation')
            ->label('Xem Đơn hàng')
            ->icon('heroicon-o-shopping-bag')
            ->color('success')
            ->visible(fn (Quotation $record): bool => (bool) ($record->converted_order_id || $record->orders()->exists()))
            ->url(fn (Quotation $record): ?string => ($order = $record->convertedOrder ?? $record->orders()->first()) ? OrderResource::getUrl('edit', ['record' => $order]) : null);
    }
}
