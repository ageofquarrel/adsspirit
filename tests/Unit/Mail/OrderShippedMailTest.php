<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Enums\OrderStatus;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use Tests\TestCase;

final class OrderShippedMailTest extends TestCase
{
    public function test_it_has_the_expected_subject_and_body(): void
    {
        $order = Order::factory()->create([
            'number' => 'MH-1048213',
            'status' => OrderStatus::Shipped,
            'shipment_carrier' => 'СДЭК',
            'shipment_tracking_number' => '1098765432',
            'shipment_tracking_url' => 'https://www.cdek.ru/ru/tracking?order_id=1098765432',
        ]);

        $mail = new OrderShippedMail($order);

        $mail->assertHasSubject('Заказ №MH-1048213 передан в доставку');
        $mail->assertSeeInText('СДЭК');
        $mail->assertSeeInText('1098765432');
        $mail->assertSeeInText('https://www.cdek.ru/ru/tracking?order_id=1098765432');
    }
}
