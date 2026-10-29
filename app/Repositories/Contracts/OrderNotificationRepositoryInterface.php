<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\OrderStatus;

interface OrderNotificationRepositoryInterface
{
    /**
     * Атомарно резервирует право отправить это уведомление.
     *
     * @return bool `true` при первом вызове для этой пары (order, type),
     *              `false` при каждом следующем вызове (уже было отправлено).
     */
    public function reserve(int $orderId, OrderStatus $type): bool;
}
