<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-30 days', '-1 days');

        return [
            'external_id' => 'ord_'.Str::upper(Str::random(8)),
            'number' => 'MH-'.fake()->unique()->numberBetween(1000000, 9999999),
            'status' => OrderStatus::Created,
            'currency' => 'RUB',
            'customer_email' => fake()->safeEmail(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->optional()->phoneNumber(),
            'delivery_price' => fake()->randomElement(['0.00', '299.00', '349.00']),
            'discount' => '0.00',
            'total' => fake()->randomFloat(2, 500, 10000),
            'shipment_carrier' => null,
            'shipment_tracking_number' => null,
            'shipment_tracking_url' => null,
            'paid_at' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'paid_at' => $attributes['updated_at'],
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Shipped,
            'shipment_carrier' => 'СДЭК',
            'shipment_tracking_number' => (string) fake()->randomNumber(9, true),
            'shipment_tracking_url' => 'https://www.cdek.ru/ru/tracking',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => $attributes['updated_at'],
            'cancel_reason' => 'Покупатель отказался от заказа',
        ]);
    }
}
