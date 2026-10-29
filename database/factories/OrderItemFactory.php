<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'sku' => Str::upper(fake()->bothify('???-###')),
            'name' => fake()->words(3, true),
            'quantity' => fake()->numberBetween(1, 3),
            'price' => fake()->randomFloat(2, 100, 3000),
        ];
    }
}
