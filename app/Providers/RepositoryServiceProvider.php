<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\MarketplaceWebhookEventRepositoryInterface;
use App\Repositories\Contracts\OrderNotificationRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\MarketplaceWebhookEventRepository;
use App\Repositories\OrderNotificationRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        MarketplaceWebhookEventRepositoryInterface::class => MarketplaceWebhookEventRepository::class,
        OrderRepositoryInterface::class => OrderRepository::class,
        OrderNotificationRepositoryInterface::class => OrderNotificationRepository::class,
    ];
}
