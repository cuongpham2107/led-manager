<?php

namespace App\Services;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuotationPdfService
{
    /**
     * Generate PDF stream response for download
     */
    public function downloadPdf(Quotation $quotation): StreamedResponse
    {
        $quotation->load(['customer', 'salesUser', 'productLine', 'items.productLine']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
        ])->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = "Bao-gia-{$quotation->code}.pdf";

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
