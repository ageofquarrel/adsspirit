<?php

use App\Http\Controllers\Api\MarketplaceWebhookController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Middleware\VerifyMarketplaceWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/marketplace', [MarketplaceWebhookController::class, 'store'])
    ->middleware(VerifyMarketplaceWebhookSignature::class);

Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
