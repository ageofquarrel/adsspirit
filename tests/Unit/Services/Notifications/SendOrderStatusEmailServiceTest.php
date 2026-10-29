<?php

namespace Tests\Unit\Services\Notifications;

use App\Enums\OrderStatus;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Services\Notifications\SendOrderStatusEmailService;
use Illuminate\Support\Facades\Mail;
use Tests\Doubles\Repositories\OrderNotificationRepositoryMock;
use Tests\Doubles\Repositories\OrderRepositoryMock;
use Tests\TestCase;

class SendOrderStatusEmailServiceTest extends TestCase
{
    private function order(OrderStatus $status): Order
    {
        return new Order([
            'id' => 'ord_1',
            'number' => 'MH-1',
            'status' => $status,
            'customer_email' => 'buyer@example.com',
        ]);
    }

    public function test_it_sends_a_paid_email_and_reserves_the_notification(): void
    {
        // Arrange
        Mail::fake();
        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $this->order(OrderStatus::Paid);
        $notificationRepository = new OrderNotificationRepositoryMock;
        $service = new SendOrderStatusEmailService($orderRepository, $notificationRepository);

        // Act
        $service->handle('ord_1', OrderStatus::Paid);

        // Assert
        Mail::assertSent(OrderPaidMail::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
        $this->assertSame(1, $notificationRepository->reserveCallCount);
        $this->assertSame(OrderStatus::Paid, $notificationRepository->lastType);
    }

    public function test_it_sends_a_shipped_email(): void
    {
        // Arrange
        Mail::fake();
        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $this->order(OrderStatus::Shipped);
        $service = new SendOrderStatusEmailService($orderRepository, new OrderNotificationRepositoryMock);

        // Act
        $service->handle('ord_1', OrderStatus::Shipped);

        // Assert
        Mail::assertSent(OrderShippedMail::class);
    }

    public function test_it_sends_a_cancelled_email(): void
    {
        // Arrange
        Mail::fake();
        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $this->order(OrderStatus::Cancelled);
        $service = new SendOrderStatusEmailService($orderRepository, new OrderNotificationRepositoryMock);

        // Act
        $service->handle('ord_1', OrderStatus::Cancelled);

        // Assert
        Mail::assertSent(OrderCancelledMail::class);
    }

    public function test_it_does_not_send_when_the_order_cannot_be_found(): void
    {
        // Arrange
        Mail::fake();
        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = null;
        $notificationRepository = new OrderNotificationRepositoryMock;
        $service = new SendOrderStatusEmailService($orderRepository, $notificationRepository);

        // Act
        $service->handle('ord_missing', OrderStatus::Paid);

        // Assert
        Mail::assertNothingSent();
        $this->assertSame(0, $notificationRepository->reserveCallCount);
    }

    public function test_it_does_not_send_a_paid_email_when_the_order_is_now_cancelled(): void
    {
        // Arrange — the job runs after a later "cancelled" event has already landed.
        Mail::fake();
        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $this->order(OrderStatus::Cancelled);
        $notificationRepository = new OrderNotificationRepositoryMock;
        $service = new SendOrderStatusEmailService($orderRepository, $notificationRepository);

        // Act
        $service->handle('ord_1', OrderStatus::Paid);

        // Assert
        Mail::assertNothingSent();
        $this->assertSame(0, $notificationRepository->reserveCallCount);
    }

    public function test_it_does_not_send_a_shipped_email_when_the_order_is_now_cancelled(): void
    {
        // Arrange
        Mail::fake();
        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $this->order(OrderStatus::Cancelled);
        $service = new SendOrderStatusEmailService($orderRepository, new OrderNotificationRepositoryMock);

        // Act
        $service->handle('ord_1', OrderStatus::Shipped);

        // Assert
        Mail::assertNothingSent();
    }

    public function test_it_does_not_send_twice_when_the_notification_was_already_reserved(): void
    {
        // Arrange
        Mail::fake();
        $orderRepository = new OrderRepositoryMock;
        $orderRepository->findReturn = $this->order(OrderStatus::Paid);
        $notificationRepository = new OrderNotificationRepositoryMock;
        $notificationRepository->reserveReturn = false;
        $service = new SendOrderStatusEmailService($orderRepository, $notificationRepository);

        // Act
        $service->handle('ord_1', OrderStatus::Paid);

        // Assert
        Mail::assertNothingSent();
    }
}
