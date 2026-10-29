<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Services\Notifications\SendOrderStatusEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendOrderStatusEmailListener implements ShouldQueue
{
    private const array NOTIFIABLE_STATUSES = [
        OrderStatus::Paid,
        OrderStatus::Shipped,
        OrderStatus::Cancelled,
    ];

    public function __construct(
        private readonly SendOrderStatusEmailService $service,
    ) {}

    /**
     * Полностью пропускает постановку в очередь для статусов без письма
     * (created/delivered), чтобы не тратить поход в очередь впустую.
     * Проверка корректности — повторное считывание текущего статуса
     * заказа перед реальной отправкой — находится в самом
     * {@see SendOrderStatusEmailService}, поскольку это должно происходить
     * в момент выполнения, а не постановки в очередь.
     */
    public function shouldQueue(OrderStatusChanged $event): bool
    {
        return in_array($event->newStatus, self::NOTIFIABLE_STATUSES, true);
    }

    public function handle(OrderStatusChanged $event): void
    {
        $this->service->handle($event->orderId, $event->newStatus);
    }
}
