<?php

use Illuminate\Support\Facades\Route;

Route::get('/{storeSlug}', [App\Http\Controllers\StorefrontController::class, 'store']);
Route::get('/{storeSlug}/{productSlug}', [App\Http\Controllers\StorefrontController::class, 'product']);

Route::prefix('admin')->group(function () {
    Route::view('/', 'admin.dashboard');
});

Route::prefix('merchant')->group(function () {
    Route::view('/', 'merchant.dashboard');
});

Route::get('/install', [App\Http\Controllers\InstallerController::class, 'index']);
Route::post('/install', [App\Http\Controllers\InstallerController::class, 'install']);
