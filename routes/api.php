<?php

use App\Http\Controllers\Api\MidtransPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments/midtrans')->group(function () {
    Route::post('charge', [MidtransPaymentController::class, 'charge']);
    Route::post('webhook', [MidtransPaymentController::class, 'webhook']);
    Route::get('transactions/{orderId}', [MidtransPaymentController::class, 'show']);
});
