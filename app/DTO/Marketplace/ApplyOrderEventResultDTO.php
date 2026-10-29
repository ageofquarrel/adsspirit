<?php

declare(strict_types=1);

namespace App\DTO\Marketplace;

use App\Enums\OrderStatus;
use App\Models\Order;

final readonly class ApplyOrderEventResultDTO
{
    public function __construct(
        /** Был ли снэпшот реально применён (false — если он устарел или пришёл не по порядку). */
        public bool $applied,
        /** Привёл ли этот вызов к переходу заказа в новый статус. */
        public bool $statusChanged,
        public ?OrderStatus $previousStatus,
        public OrderStatus $newStatus,
        public Order $order,
    ) {}
}
