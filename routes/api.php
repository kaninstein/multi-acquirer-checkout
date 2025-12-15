<?php

use Illuminate\Support\Facades\Route;
use Kaninstein\MultiAquirerCheckout\Presentation\Http\Controllers\CheckoutController;

$prefix = (string) config('multi-acquirer.routes.prefix', 'api/multi-acquirer');
$middleware = (array) config('multi-acquirer.routes.middleware', ['api']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function () {
        Route::post('/checkout', [CheckoutController::class, 'process']);
    });

