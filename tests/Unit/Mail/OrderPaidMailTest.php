<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Enums\OrderStatus;
use App\Mail\OrderPaidMail;
use App\Models\Order;
use App\Models\OrderItem;
use Tests\TestCase;

final class OrderPaidMailTest extends TestCase
{
    public function test_it_has_the_expected_subject_and_body(): void
    {
        $order = Order::factory()->create(['number' => 'MH-1048213', 'status' => OrderStatus::Paid, 'total' => '4909.50']);
        OrderItem::factory()->for($order)->create(['sku' => 'TSHIRT-BLK-M', 'name' => 'Футболка', 'quantity' => 3, 'price' => '1290.00']);
        $order->load('items');

        $mail = new OrderPaidMail($order);

        $mail->assertHasSubject('Заказ №MH-1048213 оплачен');
        $mail->assertSeeInText('Футболка');
        $mail->assertSeeInText('x3');
        $mail->assertSeeInText('4909.50');
    }
}
