<?php

namespace App\Filament\Resources\CheckinBatches\Actions;

use App\Models\CheckinBatch;
use Filament\Actions\Action;

class PrintBatchQrAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'print_batch_qr';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('PrintBatchQr:CheckinBatch')
            ->label('In mã QR')
            ->icon('heroicon-o-qr-code')
            ->color('info')
            ->button()
            ->visible(fn (CheckinBatch $record): bool => $record->items()->count() > 0)
            ->modalHeading(fn (CheckinBatch $record): string => "In mã QR — Đợt nhập kho: {$record->code}")
            ->modalContent(fn (CheckinBatch $record) => view('filament.components.batch-qr-print', [
                'batch' => $record->load(['items.asset.productLine', 'productLine', 'warehouse']),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đóng')
            ->modalWidth('7xl');
    }
}
