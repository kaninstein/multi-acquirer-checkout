<?php

use Illuminate\Support\Facades\Route;
use Kaninstein\MultiAcquirerCheckout\Presentation\Http\Controllers\CheckoutController;
use Kaninstein\MultiAcquirerCheckout\Presentation\Http\Controllers\BoletoBarcodeController;
use Kaninstein\MultiAcquirerCheckout\Presentation\Http\Controllers\FeeController;

$prefix = (string) config('multi-acquirer.routes.prefix', 'api/multi-acquirer');
$middleware = (array) config('multi-acquirer.routes.middleware', ['api']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function () {
        Route::post('/checkout', [CheckoutController::class, 'process']);
        Route::post('/fees', [FeeController::class, 'calculate']);
        Route::get('/boleto/barcode', BoletoBarcodeController::class);
    });
