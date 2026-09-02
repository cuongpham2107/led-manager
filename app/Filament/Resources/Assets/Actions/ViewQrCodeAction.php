<?php

namespace App\Filament\Resources\Assets\Actions;

use App\Models\Asset;
use Filament\Actions\Action;

class ViewQrCodeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'qr_code';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('ViewQrCode:Asset')
            ->label('Mã QR')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->button()
            ->modalHeading(fn (Asset $record): string => "Mã QR Thiết bị: {$record->serial_no}")
            ->modalContent(fn (Asset $record) => view('filament.components.asset-qr-modal', ['record' => $record]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đóng');
    }
}
