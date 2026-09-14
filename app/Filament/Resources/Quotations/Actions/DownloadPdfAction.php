<?php

namespace App\Filament\Resources\Quotations\Actions;

use App\Models\Quotation;
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
            ->authorize('DownloadPdf:Quotation')
            ->label('Tải Báo giá PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->url(fn (Quotation $record): string => route('admin.quotations.pdf', $record))
            ->openUrlInNewTab();
    }
}
