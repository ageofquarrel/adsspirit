<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\OrderStatus;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Repositories\Contracts\OrderNotificationRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Выполняется внутри воркера очереди, поэтому перед отправкой заново
 * проверяет *текущий* статус заказа: к моменту выполнения более позднее
 * событие могло уже перевести заказ в состояние, при котором это письмо
 * станет неверным (например, cancelled).
 */
class SendOrderStatusEmailService
{
    private const array NOTIFIABLE_STATUSES = [
        OrderStatus::Paid,
        OrderStatus::Shipped,
        OrderStatus::Cancelled,
    ];

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderNotificationRepositoryInterface $notificationRepository,
    ) {}

    public function handle(string $orderId, OrderStatus $type): void
    {
        $order = $this->orderRepository->find($orderId);

        if ($order === null || ! $this->isConsistentWithCurrentState($order->status, $type)) {
            return;
        }

        if (! $this->notificationRepository->reserve($order->id, $type)) {
            return;
        }

        $order->loadMissing('items');

        Mail::to($order->customer_email)->send($this->buildMailable($order, $type));
    }

    private function isConsistentWithCurrentState(OrderStatus $current, OrderStatus $type): bool
    {
        if (! in_array($type, self::NOTIFIABLE_STATUSES, true)) {
            return false;
        }

        return match ($type) {
            OrderStatus::Paid, OrderStatus::Shipped => $current !== OrderStatus::Cancelled,
            OrderStatus::Cancelled => true,
            default => false,
        };
    }

    private function buildMailable(Order $order, OrderStatus $type): Mailable
    {
        return match ($type) {
            OrderStatus::Paid => new OrderPaidMail($order),
            OrderStatus::Shipped => new OrderShippedMail($order),
            OrderStatus::Cancelled => new OrderCancelledMail($order),
            default => throw new InvalidArgumentException("Unsupported notification type: {$type->value}"),
        };
    }
}
