<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Генерирует фейковыми данными реалистичный набор заказов по всем
 * статусам, с правдоподобной историей статусов и позициями — чтобы
 * в админке и API было что фильтровать и листать при ручном тестировании.
 */
final class OrderSeeder extends Seeder
{
    private const int ORDERS_PER_STATUS = 8;

    public function run(): void
    {
        foreach (OrderStatus::cases() as $status) {
            for ($i = 0; $i < self::ORDERS_PER_STATUS; $i++) {
                $this->createOrder($status);
            }
        }
    }

    private function createOrder(OrderStatus $finalStatus): void
    {
        $path = $this->statusPath($finalStatus);
        $createdAt = CarbonImmutable::instance(fake()->dateTimeBetween('-60 days', '-1 days'));

        $attributes = ['status' => $finalStatus, 'created_at' => $createdAt];
        $history = [];
        $timestamp = $createdAt;

        foreach ($path as $status) {
            $history[] = ['status' => $status, 'changed_at' => $timestamp];

            match ($status) {
                OrderStatus::Paid => $attributes['paid_at'] = $timestamp,
                OrderStatus::Shipped => $attributes += [
                    'shipment_carrier' => fake()->randomElement(['СДЭК', 'Почта России', 'Boxberry']),
                    'shipment_tracking_number' => (string) fake()->numberBetween(1000000000, 9999999999),
                    'shipment_tracking_url' => 'https://example.com/track/'.fake()->uuid(),
                ],
                OrderStatus::Cancelled => $attributes += [
                    'cancelled_at' => $timestamp,
                    'cancel_reason' => fake()->randomElement([
                        'Покупатель отказался от заказа',
                        'Нет в наличии',
                        'Не удалось связаться с покупателем',
                    ]),
                ],
                default => null,
            };

            $timestamp = $timestamp->addHours(fake()->numberBetween(2, 72));
        }

        $attributes['updated_at'] = $history[array_key_last($history)]['changed_at'];

        $order = Order::factory()->create($attributes);

        OrderItem::factory()->for($order)->count(fake()->numberBetween(1, 4))->create();

        foreach ($history as $entry) {
            $order->statusHistory()->create($entry);
        }
    }

    /**
     * @return OrderStatus[]
     */
    private function statusPath(OrderStatus $finalStatus): array
    {
        return match ($finalStatus) {
            OrderStatus::Created => [OrderStatus::Created],
            OrderStatus::Paid => [OrderStatus::Created, OrderStatus::Paid],
            OrderStatus::Shipped => [OrderStatus::Created, OrderStatus::Paid, OrderStatus::Shipped],
            OrderStatus::Delivered => [
                OrderStatus::Created, OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered,
            ],
            OrderStatus::Cancelled => fake()->boolean(50)
                ? [OrderStatus::Created, OrderStatus::Paid, OrderStatus::Cancelled]
                : [OrderStatus::Created, OrderStatus::Cancelled],
        };
    }
}
