<?php

namespace App\Providers;

use App\Repositories\Contracts\MarketplaceWebhookEventRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\MarketplaceWebhookEventRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        MarketplaceWebhookEventRepositoryInterface::class => MarketplaceWebhookEventRepository::class,
        OrderRepositoryInterface::class => OrderRepository::class,
    ];
}
