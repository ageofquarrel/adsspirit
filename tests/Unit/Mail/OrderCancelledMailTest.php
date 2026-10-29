<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Enums\OrderStatus;
use App\Mail\OrderCancelledMail;
use App\Models\Order;
use Tests\TestCase;

final class OrderCancelledMailTest extends TestCase
{
    public function test_it_mentions_the_refund_when_the_order_was_paid(): void
    {
        $order = Order::factory()->create([
            'number' => 'MH-1048213',
            'status' => OrderStatus::Cancelled,
            'cancel_reason' => 'Покупатель отказался от заказа',
            'paid_at' => now(),
        ]);

        $mail = new OrderCancelledMail($order);

        $mail->assertHasSubject('Заказ №MH-1048213 отменён');
        $mail->assertSeeInText('Покупатель отказался от заказа');
        $mail->assertSeeInText('10 дней');
    }

    public function test_it_does_not_mention_the_refund_when_the_order_was_never_paid(): void
    {
        $order = Order::factory()->create([
            'number' => 'MH-1048213',
            'status' => OrderStatus::Cancelled,
            'cancel_reason' => 'Покупатель отказался от заказа',
            'paid_at' => null,
        ]);

        $mail = new OrderCancelledMail($order);

        $mail->assertDontSeeInText('10 дней');
    }
}
