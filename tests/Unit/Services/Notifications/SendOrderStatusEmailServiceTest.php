<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Enums\OrderStatus;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Services\Notifications\SendOrderStatusEmailService;
use Illuminate\Support\Facades\Mail;
use Tests\Mocks\Internal\App\Repositories\Contracts\OrderNotificationRepositoryMock;
use Tests\Mocks\Internal\App\Repositories\Contracts\OrderRepositoryMock;
use Tests\TestCase;

final class SendOrderStatusEmailServiceTest extends TestCase
{
    private function order(OrderStatus $status): Order
    {
        return new Order([
            'id' => 1,
            'external_id' => 'ord_1',
            'number' => 'MH-1',
            'status' => $status,
            'customer_email' => 'buyer@example.com',
        ]);
    }

    public function test_it_sends_a_paid_email_and_reserves_the_notification(): void
    {
        Mail::fake();
        OrderRepositoryMock::returnFind($this->order(OrderStatus::Paid), 'ord_1');
        OrderNotificationRepositoryMock::returnReserve(true);

        app(SendOrderStatusEmailService::class)->handle('ord_1', OrderStatus::Paid);

        Mail::assertSent(OrderPaidMail::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
    }

    public function test_it_sends_a_shipped_email(): void
    {
        Mail::fake();
        OrderRepositoryMock::returnFind($this->order(OrderStatus::Shipped), 'ord_1');
        OrderNotificationRepositoryMock::returnReserve(true);

        app(SendOrderStatusEmailService::class)->handle('ord_1', OrderStatus::Shipped);

        Mail::assertSent(OrderShippedMail::class);
    }

    public function test_it_sends_a_cancelled_email(): void
    {
        Mail::fake();
        OrderRepositoryMock::returnFind($this->order(OrderStatus::Cancelled), 'ord_1');
        OrderNotificationRepositoryMock::returnReserve(true);

        app(SendOrderStatusEmailService::class)->handle('ord_1', OrderStatus::Cancelled);

        Mail::assertSent(OrderCancelledMail::class);
    }

    public function test_it_does_not_send_when_the_order_cannot_be_found(): void
    {
        Mail::fake();
        OrderRepositoryMock::returnFind(null, 'ord_missing');

        app(SendOrderStatusEmailService::class)->handle('ord_missing', OrderStatus::Paid);

        Mail::assertNothingSent();
    }

    public function test_it_does_not_send_a_paid_email_when_the_order_is_now_cancelled(): void
    {
        Mail::fake();
        OrderRepositoryMock::returnFind($this->order(OrderStatus::Cancelled), 'ord_1');

        app(SendOrderStatusEmailService::class)->handle('ord_1', OrderStatus::Paid);

        Mail::assertNothingSent();
    }

    public function test_it_does_not_send_a_shipped_email_when_the_order_is_now_cancelled(): void
    {
        Mail::fake();
        OrderRepositoryMock::returnFind($this->order(OrderStatus::Cancelled), 'ord_1');

        app(SendOrderStatusEmailService::class)->handle('ord_1', OrderStatus::Shipped);

        Mail::assertNothingSent();
    }

    public function test_it_does_not_send_twice_when_the_notification_was_already_reserved(): void
    {
        Mail::fake();
        OrderRepositoryMock::returnFind($this->order(OrderStatus::Paid), 'ord_1');
        OrderNotificationRepositoryMock::returnReserve(false);

        app(SendOrderStatusEmailService::class)->handle('ord_1', OrderStatus::Paid);

        Mail::assertNothingSent();
    }
}
