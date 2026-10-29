<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Вызывается всякий раз, когда применение снэпшота маркетплейса
 * переводит заказ в новый статус (никогда — для устаревшего/пришедшего
 * не по порядку снэпшота, и никогда — если статус фактически не
 * изменился, например при событии `order.updated`).
 */
final readonly class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(
        public string $orderId,
        public ?OrderStatus $previousStatus,
        public OrderStatus $newStatus,
    ) {}
}
