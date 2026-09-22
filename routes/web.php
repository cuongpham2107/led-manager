<?php

use App\Exports\AssetTemplateExport;
use App\Exports\CheckinBatchTemplate;
use App\Http\Controllers\CheckinAssetSearchController;
use App\Http\Controllers\CheckinImportController;
use App\Http\Controllers\CheckoutAssetSearchController;
use App\Http\Controllers\PublicAssetController;
use App\Http\Controllers\ReturnAssetController;
use App\Models\Quotation;
use App\Services\QuotationPdfService;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

// Public asset inspection — QR scan (no auth required)
Route::middleware('throttle:60,1')
    ->get('/q/{code}', [PublicAssetController::class, 'show'])
    ->name('asset.public.show');

Route::get('/mobile', function () {
    $indexPath = public_path('mobile/index.html');
    if (! file_exists($indexPath)) {
        abort(404, 'Mobile web bundle not found. Please run npm run build:web inside /mobile directory.');
    }

    return response(file_get_contents($indexPath), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->name('mobile.web');

Route::get('/scanner', function () {
    return redirect('/mobile');
})->name('scanner.redirect');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/filament/checkin-batch-template', function () {
        return Excel::download(new CheckinBatchTemplate, 'mau-import-checkin.xlsx');
    })->name('filament.checkin-batch-template');

    Route::get('/filament/asset-import-template', function () {
        return Excel::download(new AssetTemplateExport, 'mau-import-thiet-bi.xlsx');
    })->name('filament.asset-template');

    Route::post('/filament-api/checkin-import', [CheckinImportController::class, 'import'])
        ->name('filament.checkin-import');

    Route::get('/filament-api/checkin-assets', [CheckinAssetSearchController::class, 'index'])
        ->name('filament.checkin-assets');

    Route::post('/filament-api/checkin-receive-item', [CheckinAssetSearchController::class, 'receiveItem'])
        ->name('filament.checkin-receive-item');

    Route::post('/filament-api/checkin-complete-batch', [CheckinAssetSearchController::class, 'completeBatch'])
        ->name('filament.checkin-complete-batch');

    Route::get('/filament-api/checkout-assets', [CheckoutAssetSearchController::class, 'index'])
        ->name('filament.checkout-assets');

    Route::post('/filament-api/checkout-dispatch-item', [CheckoutAssetSearchController::class, 'dispatchItem'])
        ->name('filament.checkout-dispatch-item');

    Route::post('/filament-api/return-receive-item', [ReturnAssetController::class, 'receiveItem'])
        ->name('filament.return-receive-item');

    Route::post('/filament-api/return-complete-batch', [ReturnAssetController::class, 'completeBatch'])
        ->name('filament.return-complete-batch');

    Route::get('/admin/quotations/{quotation}/pdf', function (Quotation $quotation, QuotationPdfService $service) {
        return $service->downloadPdf($quotation);
    })->name('admin.quotations.pdf');
});
