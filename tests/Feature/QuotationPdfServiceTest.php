<?php

use App\Models\Quotation;
use App\Services\QuotationPdfService;
use Database\Seeders\LedOsDataSeeder;

test('quotation pdf service generates valid pdf download response', function () {
    (new LedOsDataSeeder)->run();

    $quotation = Quotation::with(['customer', 'items.productLine', 'productLine'])->first();
    expect($quotation)->not->toBeNull();

    $pdfService = new QuotationPdfService;
    $response = $pdfService->downloadPdf($quotation);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-type'))->toBe('application/pdf');
});
