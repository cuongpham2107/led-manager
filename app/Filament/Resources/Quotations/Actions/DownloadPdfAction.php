<?php

namespace App\Filament\Resources\Quotations\Actions;

use App\Models\Quotation;
use App\Services\QuotationPdfService;
use Filament\Actions\Action;

class DownloadPdfAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'download_pdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tải Báo giá PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(fn (Quotation $record) => app(QuotationPdfService::class)->downloadPdf($record));
    }
}
