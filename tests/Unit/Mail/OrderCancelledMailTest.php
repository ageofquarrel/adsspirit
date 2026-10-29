<?php

namespace Tests\Unit\Mail;

use App\Enums\OrderStatus;
use App\Mail\OrderCancelledMail;
use App\Models\Order;
use Tests\TestCase;

class OrderCancelledMailTest extends TestCase
{
    public function test_it_mentions_the_refund_when_the_order_was_paid(): void
    {
        // Arrange
        $order = Order::factory()->create([
            'number' => 'MH-1048213',
            'status' => OrderStatus::Cancelled,
            'cancel_reason' => 'Покупатель отказался от заказа',
            'paid_at' => now(),
        ]);

        // Act
        $mail = new OrderCancelledMail($order);

        // Assert
        $mail->assertHasSubject('Заказ №MH-1048213 отменён');
        $mail->assertSeeInText('Покупатель отказался от заказа');
        $mail->assertSeeInText('10 дней');
    }

    public function test_it_does_not_mention_the_refund_when_the_order_was_never_paid(): void
    {
        // Arrange
        $order = Order::factory()->create([
            'number' => 'MH-1048213',
            'status' => OrderStatus::Cancelled,
            'cancel_reason' => 'Покупатель отказался от заказа',
            'paid_at' => null,
        ]);

        // Act
        $mail = new OrderCancelledMail($order);

        // Assert
        $mail->assertDontSeeInText('10 дней');
    }
}
