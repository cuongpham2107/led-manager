<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Filament\Resources\Contracts\ContractResource;
use App\Models\Order;
use Filament\Actions\Action;

class ViewContractAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'view_contract';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('ViewContract:Order')
            ->label('Xem Hợp đồng')
            ->icon('heroicon-o-document-check')
            ->color('info')
            ->visible(fn (Order $record): bool => $record->contracts->isNotEmpty())
            ->url(fn (Order $record): ?string => ($contract = $record->contracts->first()) ? ContractResource::getUrl('edit', ['record' => $contract]) : null);
    }
}
