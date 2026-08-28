<?php

use App\Http\Controllers\Api\V1\AssetApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckoutBatchApiController;
use App\Http\Controllers\Api\V1\ReturnBatchApiController;
use App\Http\Controllers\Api\V1\WarehouseApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public Auth
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth profile & logout
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // Warehouses
        Route::get('/warehouses', [WarehouseApiController::class, 'index'])->name('api.v1.warehouses.index');

        // Asset Lookup (Scan QR code / Serial info)
        Route::get('/assets/lookup', [AssetApiController::class, 'lookup'])->name('api.v1.assets.lookup');

        // Outbound (Checkout Batches)
        Route::get('/checkout-batches', [CheckoutBatchApiController::class, 'index'])->name('api.v1.checkout-batches.index');
        Route::get('/checkout-batches/{id}', [CheckoutBatchApiController::class, 'show'])->name('api.v1.checkout-batches.show');
        Route::post('/checkout-batches/{id}/scan', [CheckoutBatchApiController::class, 'scan'])->name('api.v1.checkout-batches.scan');
        Route::post('/checkout-batches/{id}/complete', [CheckoutBatchApiController::class, 'complete'])->name('api.v1.checkout-batches.complete');

        // Inbound / Returns (Return Batches)
        Route::get('/return-batches', [ReturnBatchApiController::class, 'index'])->name('api.v1.return-batches.index');
        Route::get('/return-batches/{id}', [ReturnBatchApiController::class, 'show'])->name('api.v1.return-batches.show');
        Route::post('/return-batches/{id}/scan', [ReturnBatchApiController::class, 'scan'])->name('api.v1.return-batches.scan');
        Route::post('/return-batches/{id}/complete', [ReturnBatchApiController::class, 'complete'])->name('api.v1.return-batches.complete');
    });
});
