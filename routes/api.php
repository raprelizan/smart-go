<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('auth/register', [App\Http\Controllers\Api\MerchantRegistrationController::class, 'register']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('me', [App\Http\Controllers\Api\AuthController::class, 'me']);
        Route::post('auth/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);

        Route::middleware(['tenant'])->group(function () {
            Route::apiResource('stores', App\Http\Controllers\Api\StoreController::class);
            Route::apiResource('products', App\Http\Controllers\Api\ProductController::class);
            Route::apiResource('pages', App\Http\Controllers\Api\PageController::class);
            Route::apiResource('orders', App\Http\Controllers\Api\OrderController::class);
            Route::apiResource('shipping-rules', App\Http\Controllers\Api\ShippingRuleController::class);
            Route::get('wilayas', [App\Http\Controllers\Api\WilayaController::class, 'index']);
            Route::post('notifications/test', [App\Http\Controllers\Api\NotificationController::class, 'test']);
        });

        Route::middleware(['role:super-admin'])->group(function () {
            Route::get('admin/merchants', [App\Http\Controllers\Api\Admin\MerchantApprovalController::class, 'index']);
            Route::post('admin/merchants/{merchant}/approve', [App\Http\Controllers\Api\Admin\MerchantApprovalController::class, 'approve']);
            Route::post('admin/merchants/{merchant}/reject', [App\Http\Controllers\Api\Admin\MerchantApprovalController::class, 'reject']);
            Route::apiResource('admin/plugins', App\Http\Controllers\Api\Admin\PluginController::class);
        });
    });
});
