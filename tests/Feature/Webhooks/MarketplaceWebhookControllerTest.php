<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Enums\OrderStatus;
use App\Models\MarketplaceWebhookEvent;
use App\Models\Order;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class MarketplaceWebhookControllerTest extends TestCase
{
    private const string SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.marketplace.webhook_secret' => self::SECRET]);
    }

    /**
     * @return array{timestamp: string, signature: string}
     */
    private function sign(string $body, ?string $timestamp = null): array
    {
        $timestamp ??= (string) now()->timestamp;

        return [
            'timestamp' => $timestamp,
            'signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, self::SECRET),
        ];
    }

    private function postWebhook(string $body, array $headers): TestResponse
    {
        $server = $this->transformHeadersToServerVars($headers + ['Content-Type' => 'application/json']);

        return $this->call('POST', '/api/webhooks/marketplace', [], [], [], $server, $body);
    }

    public function test_a_validly_signed_event_is_accepted_and_stored(): void
    {
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $signed = $this->sign($body);

        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => $signed['signature'],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('marketplace_webhook_events', [
            'event_id' => $event['id'],
            'type' => $event['type'],
        ]);
        $this->assertNotNull(
            MarketplaceWebhookEvent::query()->where('event_id', $event['id'])->first()->processed_at,
        );
        $this->assertDatabaseHas('orders', [
            'external_id' => $event['data']['order']['id'],
            'status' => OrderStatus::Paid->value,
        ]);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $signed = $this->sign($body);

        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => 'sha256=0000000000000000000000000000000000000000000000000000000000000000',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('marketplace_webhook_events', ['event_id' => $event['id']]);
    }

    public function test_missing_headers_are_rejected(): void
    {
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $signed = $this->sign($body);

        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
        ]);

        $response->assertStatus(400);
    }

    public function test_a_stale_timestamp_is_rejected(): void
    {
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $staleTimestamp = (string) now()->subMinutes(10)->timestamp;
        $signed = $this->sign($body, $staleTimestamp);

        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => $signed['signature'],
        ]);

        $response->assertStatus(401);
    }

    public function test_a_malformed_body_is_rejected_with_422(): void
    {
        $body = json_encode(['id' => 'evt_1']);
        $signed = $this->sign($body);

        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => 'evt_1',
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => $signed['signature'],
        ]);

        $response->assertStatus(422);
    }

    public function test_a_replayed_event_id_is_accepted_but_not_stored_twice(): void
    {
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $firstSignature = $this->sign($body);

        $firstResponse = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $firstSignature['timestamp'],
            'X-Webhook-Signature' => $firstSignature['signature'],
        ]);

        $secondSignature = $this->sign($body, (string) (((int) $firstSignature['timestamp']) + 5));
        $secondResponse = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $secondSignature['timestamp'],
            'X-Webhook-Signature' => $secondSignature['signature'],
        ]);

        $firstResponse->assertOk();
        $secondResponse->assertOk();
        $this->assertSame(
            1,
            MarketplaceWebhookEvent::query()->where('event_id', $event['id'])->count(),
        );
    }

    public function test_a_retry_after_a_crash_between_storing_and_applying_still_completes(): void
    {
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        MarketplaceWebhookEvent::query()->create([
            'event_id' => $event['id'],
            'type' => $event['type'],
            'payload' => $event,
            'processed_at' => null,
        ]);
        $signed = $this->sign($body);

        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => $signed['signature'],
        ]);

        $response->assertOk();
        $this->assertSame(
            1,
            MarketplaceWebhookEvent::query()->where('event_id', $event['id'])->count(),
        );
        $this->assertNotNull(
            MarketplaceWebhookEvent::query()->where('event_id', $event['id'])->first()->processed_at,
        );
        $this->assertDatabaseHas('orders', ['external_id' => $event['data']['order']['id']]);
    }

    public function test_a_realistic_sequence_of_events_builds_up_the_order_and_its_history(): void
    {
        foreach (['order-created.json', 'order-paid.json', 'order-shipped.json'] as $index => $file) {
            $body = file_get_contents(base_path("task/examples/{$file}"));
            $event = json_decode($body, true);
            $signed = $this->sign($body, (string) (now()->timestamp + $index));

            $this->postWebhook($body, [
                'X-Webhook-Id' => $event['id'],
                'X-Webhook-Timestamp' => $signed['timestamp'],
                'X-Webhook-Signature' => $signed['signature'],
            ])->assertOk();
        }

        $order = Order::query()->with('statusHistory', 'items')->where('external_id', 'ord_7Q2KX9M4')->firstOrFail();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame(
            [OrderStatus::Created, OrderStatus::Paid, OrderStatus::Shipped],
            $order->statusHistory->pluck('status')->all(),
        );
        $this->assertSame('СДЭК', $order->shipment_carrier);
    }

    public function test_an_out_of_order_delivery_does_not_regress_the_order_state(): void
    {
        $shippedBody = file_get_contents(base_path('task/examples/order-shipped.json'));
        $shippedEvent = json_decode($shippedBody, true);
        $shippedSignature = $this->sign($shippedBody, (string) now()->timestamp);
        $this->postWebhook($shippedBody, [
            'X-Webhook-Id' => $shippedEvent['id'],
            'X-Webhook-Timestamp' => $shippedSignature['timestamp'],
            'X-Webhook-Signature' => $shippedSignature['signature'],
        ])->assertOk();

        $paidBody = file_get_contents(base_path('task/examples/order-paid.json'));
        $paidEvent = json_decode($paidBody, true);
        $paidEvent['id'] = 'evt_late_duplicate';
        $paidBody = json_encode($paidEvent);
        $paidSignature = $this->sign($paidBody, (string) (now()->timestamp + 1));

        $this->postWebhook($paidBody, [
            'X-Webhook-Id' => $paidEvent['id'],
            'X-Webhook-Timestamp' => $paidSignature['timestamp'],
            'X-Webhook-Signature' => $paidSignature['signature'],
        ])->assertOk();

        $order = Order::query()->where('external_id', 'ord_7Q2KX9M4')->firstOrFail();
        $this->assertSame(OrderStatus::Shipped, $order->status);
    }
}
