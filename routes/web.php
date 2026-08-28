<?php

use Illuminate\Support\Facades\Route;

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
