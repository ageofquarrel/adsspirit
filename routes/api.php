<?php

use App\Http\Controllers\Api\MarketplaceWebhookController;
use App\Http\Middleware\VerifyMarketplaceWebhookSignature;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/marketplace', MarketplaceWebhookController::class)
    ->middleware(VerifyMarketplaceWebhookSignature::class);
