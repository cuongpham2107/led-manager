<?php

namespace App\Services;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class QuotationPdfService
{
    /**
     * Generate PDF stream response for download
     */
    public function downloadPdf(Quotation $quotation): Response
    {
        $quotation->load(['customer', 'salesUser', 'productLine', 'items.deviceType']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
        ])->setPaper('a4', 'portrait');

        $filename = "Bao-gia-{$quotation->code}.pdf";

        return $pdf->download($filename);
    }
}
