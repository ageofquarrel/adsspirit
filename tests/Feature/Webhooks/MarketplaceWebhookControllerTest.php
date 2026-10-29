<?php

namespace Tests\Feature\Webhooks;

use App\Enums\OrderStatus;
use App\Models\MarketplaceWebhookEvent;
use App\Models\Order;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MarketplaceWebhookControllerTest extends TestCase
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
        // Arrange
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $signed = $this->sign($body);

        // Act
        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => $signed['signature'],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('marketplace_webhook_events', [
            'event_id' => $event['id'],
            'type' => $event['type'],
        ]);
        $this->assertNotNull(
            MarketplaceWebhookEvent::query()->where('event_id', $event['id'])->first()->processed_at,
        );
        $this->assertDatabaseHas('orders', [
            'id' => $event['data']['order']['id'],
            'status' => OrderStatus::Paid->value,
        ]);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        // Arrange
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $signed = $this->sign($body);

        // Act
        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => 'sha256=0000000000000000000000000000000000000000000000000000000000000000',
        ]);

        // Assert
        $response->assertStatus(401);
        $this->assertDatabaseMissing('marketplace_webhook_events', ['event_id' => $event['id']]);
    }

    public function test_missing_headers_are_rejected(): void
    {
        // Arrange
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $signed = $this->sign($body);

        // Act
        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            // X-Webhook-Signature intentionally omitted.
        ]);

        // Assert
        $response->assertStatus(400);
    }

    public function test_a_stale_timestamp_is_rejected(): void
    {
        // Arrange
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $staleTimestamp = (string) now()->subMinutes(10)->timestamp;
        $signed = $this->sign($body, $staleTimestamp);

        // Act
        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => $signed['signature'],
        ]);

        // Assert
        $response->assertStatus(401);
    }

    public function test_a_malformed_body_is_rejected_with_422(): void
    {
        // Arrange
        $body = json_encode(['id' => 'evt_1']);
        $signed = $this->sign($body);

        // Act
        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => 'evt_1',
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => $signed['signature'],
        ]);

        // Assert
        $response->assertStatus(422);
    }

    public function test_a_replayed_event_id_is_accepted_but_not_stored_twice(): void
    {
        // Arrange
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $firstSignature = $this->sign($body);

        // Act
        $firstResponse = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $firstSignature['timestamp'],
            'X-Webhook-Signature' => $firstSignature['signature'],
        ]);

        // A real retry arrives with a new timestamp and signature, same event id.
        $secondSignature = $this->sign($body, (string) (((int) $firstSignature['timestamp']) + 5));
        $secondResponse = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $secondSignature['timestamp'],
            'X-Webhook-Signature' => $secondSignature['signature'],
        ]);

        // Assert
        $firstResponse->assertOk();
        $secondResponse->assertOk();
        $this->assertSame(
            1,
            MarketplaceWebhookEvent::query()->where('event_id', $event['id'])->count(),
        );
    }

    public function test_a_retry_after_a_crash_between_storing_and_applying_still_completes(): void
    {
        // Arrange — simulate a worker crash: the event got stored (e.g. by a
        // previous, failed delivery attempt) but never marked processed, so
        // the order was never actually created.
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        MarketplaceWebhookEvent::query()->create([
            'event_id' => $event['id'],
            'type' => $event['type'],
            'payload' => $event,
            'processed_at' => null,
        ]);
        $signed = $this->sign($body);

        // Act — MarketHub retries with the same event id, a new timestamp/signature.
        $response = $this->postWebhook($body, [
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $signed['timestamp'],
            'X-Webhook-Signature' => $signed['signature'],
        ]);

        // Assert
        $response->assertOk();
        $this->assertSame(
            1,
            MarketplaceWebhookEvent::query()->where('event_id', $event['id'])->count(),
        );
        $this->assertNotNull(
            MarketplaceWebhookEvent::query()->where('event_id', $event['id'])->first()->processed_at,
        );
        $this->assertDatabaseHas('orders', ['id' => $event['data']['order']['id']]);
    }

    public function test_a_realistic_sequence_of_events_builds_up_the_order_and_its_history(): void
    {
        // Arrange & Act — created, then paid, then shipped, as they would really arrive.
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

        // Assert
        $order = Order::query()->with('statusHistory', 'items')->findOrFail('ord_7Q2KX9M4');
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame(
            [OrderStatus::Created, OrderStatus::Paid, OrderStatus::Shipped],
            $order->statusHistory->pluck('status')->all(),
        );
        $this->assertSame('СДЭК', $order->shipment_carrier);
    }

    public function test_an_out_of_order_delivery_does_not_regress_the_order_state(): void
    {
        // Arrange — "shipped" arrives first, then a late "paid" duplicate shows up.
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
        // Different event id (as a distinct, late redelivery would have), same order, older snapshot.
        $paidEvent['id'] = 'evt_late_duplicate';
        $paidBody = json_encode($paidEvent);
        $paidSignature = $this->sign($paidBody, (string) (now()->timestamp + 1));

        // Act
        $this->postWebhook($paidBody, [
            'X-Webhook-Id' => $paidEvent['id'],
            'X-Webhook-Timestamp' => $paidSignature['timestamp'],
            'X-Webhook-Signature' => $paidSignature['signature'],
        ])->assertOk();

        // Assert — order is still "shipped", not regressed back to "paid".
        $order = Order::query()->findOrFail('ord_7Q2KX9M4');
        $this->assertSame(OrderStatus::Shipped, $order->status);
    }
}
