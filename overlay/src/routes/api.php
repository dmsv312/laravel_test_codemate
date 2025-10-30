<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WalletController;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\IdempotencyMiddleware;

Route::middleware([ApiKeyMiddleware::class])->group(function () {
    Route::get('/balance/{user}', [WalletController::class, 'balance']);

    Route::middleware([IdempotencyMiddleware::class])->group(function () {
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::post('/withdraw', [WalletController::class, 'withdraw']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
    });
});
