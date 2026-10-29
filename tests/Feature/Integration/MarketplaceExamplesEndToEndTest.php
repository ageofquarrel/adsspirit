<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Enums\OrderStatus;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Прогоняет каждую заглушку из task/examples/ через реальный HTTP-эндпоинт,
 * подписывая её точно так же, как task/examples/send-event.php, и проверяет
 * весь конвейер целиком: приём вебхука -> состояние заказа -> письма
 * покупателю -> то, что видно через API админки.
 */
final class MarketplaceExamplesEndToEndTest extends TestCase
{
    private const string SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.marketplace.webhook_secret' => self::SECRET]);
    }

    private function send(string $file, int $timestampOffset): void
    {
        $body = file_get_contents(base_path("task/examples/{$file}"));
        $event = json_decode($body, true);
        $timestamp = (string) (now()->timestamp + $timestampOffset);
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, self::SECRET);

        $server = $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
        ]);

        $this->call('POST', '/api/webhooks/marketplace', [], [], [], $server, $body)->assertOk();
    }

    public function test_every_example_event_drives_the_system_to_the_expected_end_state(): void
    {
        Mail::fake();

        $this->send('order-created.json', 0);
        $this->send('order-updated.json', 1);
        $this->send('order-paid.json', 2);
        $this->send('order-shipped.json', 3);
        $this->send('order-cancelled.json', 4);

        $shipped = Order::query()->with('items', 'statusHistory')->where('external_id', 'ord_7Q2KX9M4')->firstOrFail();
        $this->assertSame(OrderStatus::Shipped, $shipped->status);
        $this->assertSame(
            [OrderStatus::Created, OrderStatus::Paid, OrderStatus::Shipped],
            $shipped->statusHistory->pluck('status')->all(),
        );
        $this->assertSame('СДЭК', $shipped->shipment_carrier);
        $this->assertCount(2, $shipped->items);

        $cancelled = Order::query()->where('external_id', 'ord_3HV8PW2C')->firstOrFail();
        $this->assertSame(OrderStatus::Cancelled, $cancelled->status);
        $this->assertNotNull($cancelled->paid_at);
        $this->assertSame('Покупатель отказался от заказа', $cancelled->cancel_reason);

        Mail::assertSent(OrderPaidMail::class, 1);
        Mail::assertSent(OrderShippedMail::class, 1);
        Mail::assertSent(OrderCancelledMail::class, function (OrderCancelledMail $mail) {
            return $mail->order->external_id === 'ord_3HV8PW2C';
        });
        Mail::assertSent(OrderCancelledMail::class, 1);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/orders?status=cancelled')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'ord_3HV8PW2C');

        $this->getJson('/api/orders/ord_7Q2KX9M4')
            ->assertOk()
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.history.2.status', 'shipped');
    }

    public function test_replaying_every_example_event_a_second_time_changes_nothing(): void
    {
        Mail::fake();
        $files = ['order-created.json', 'order-updated.json', 'order-paid.json', 'order-shipped.json', 'order-cancelled.json'];

        foreach ($files as $index => $file) {
            $this->send($file, $index);
        }

        $ordersBefore = Order::query()->count();

        foreach ($files as $index => $file) {
            $this->send($file, 100 + $index);
        }

        $this->assertSame($ordersBefore, Order::query()->count());
        Mail::assertSent(OrderPaidMail::class, 1);
        Mail::assertSent(OrderShippedMail::class, 1);
        Mail::assertSent(OrderCancelledMail::class, 1);
    }
}
