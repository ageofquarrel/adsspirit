<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Repositories\Contracts\OrderNotificationRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class OrderNotificationRepository implements OrderNotificationRepositoryInterface
{
    public function reserve(string $orderId, OrderStatus $type): bool
    {
        $inserted = DB::table('order_notifications')->insertOrIgnore([
            'order_id' => $orderId,
            'type' => $type->value,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $inserted > 0;
    }
}
