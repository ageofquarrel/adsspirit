<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Services\Notifications\SendOrderStatusEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusEmailListener implements ShouldQueue
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
     * Skips queuing entirely for statuses with no email (created/delivered),
     * instead of wasting a queue round-trip just to no-op. The *correctness*
     * guard — re-checking the order's current status before actually
     * sending — lives in {@see SendOrderStatusEmailService} itself, since
     * that has to happen at execution time, not at dispatch time.
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
