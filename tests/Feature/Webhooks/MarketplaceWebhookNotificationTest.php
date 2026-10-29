<?php

namespace Tests\Feature\Webhooks;

use App\Enums\OrderStatus;
use App\Jobs\SendOrderStatusEmailJob;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Services\Notifications\SendOrderStatusEmailService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MarketplaceWebhookNotificationTest extends TestCase
{
    private const string SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.marketplace.webhook_secret' => self::SECRET]);
    }

    private function postExample(string $file, ?string $timestamp = null): TestResponse
    {
        $body = file_get_contents(base_path("task/examples/{$file}"));
        $event = json_decode($body, true);
        $timestamp ??= (string) now()->timestamp;
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, self::SECRET);

        $server = $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
        ]);

        return $this->call('POST', '/api/webhooks/marketplace', [], [], [], $server, $body);
    }

    public function test_a_paid_event_sends_the_paid_email_to_the_customer(): void
    {
        // Arrange
        Mail::fake();

        // Act — the queue runs synchronously in tests (QUEUE_CONNECTION=sync).
        $this->postExample('order-paid.json')->assertOk();

        // Assert
        Mail::assertSent(OrderPaidMail::class, fn ($mail) => $mail->hasTo('anna.smirnova@example.com'));
    }

    public function test_a_shipped_event_sends_the_shipped_email(): void
    {
        // Arrange
        Mail::fake();

        // Act
        $this->postExample('order-shipped.json')->assertOk();

        // Assert
        Mail::assertSent(OrderShippedMail::class);
    }

    public function test_a_cancelled_event_sends_the_cancelled_email(): void
    {
        // Arrange
        Mail::fake();

        // Act
        $this->postExample('order-cancelled.json')->assertOk();

        // Assert
        Mail::assertSent(OrderCancelledMail::class);
    }

    public function test_a_created_event_does_not_send_any_email(): void
    {
        // Arrange
        Mail::fake();

        // Act
        $this->postExample('order-created.json')->assertOk();

        // Assert
        Mail::assertNothingSent();
    }

    public function test_replaying_the_same_paid_event_does_not_send_the_email_twice(): void
    {
        // Arrange
        Mail::fake();
        $body = file_get_contents(base_path('task/examples/order-paid.json'));
        $event = json_decode($body, true);
        $firstTimestamp = (string) now()->timestamp;
        $firstSignature = 'sha256='.hash_hmac('sha256', $firstTimestamp.'.'.$body, self::SECRET);

        $server = $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $firstTimestamp,
            'X-Webhook-Signature' => $firstSignature,
        ]);
        $this->call('POST', '/api/webhooks/marketplace', [], [], [], $server, $body)->assertOk();

        // Act — a real retry: same event id, new timestamp/signature.
        $secondTimestamp = (string) (((int) $firstTimestamp) + 5);
        $secondSignature = 'sha256='.hash_hmac('sha256', $secondTimestamp.'.'.$body, self::SECRET);
        $server = $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'X-Webhook-Id' => $event['id'],
            'X-Webhook-Timestamp' => $secondTimestamp,
            'X-Webhook-Signature' => $secondSignature,
        ]);
        $this->call('POST', '/api/webhooks/marketplace', [], [], [], $server, $body)->assertOk();

        // Assert
        Mail::assertSent(OrderPaidMail::class, 1);
    }

    public function test_a_paid_order_that_is_cancelled_before_the_job_runs_never_gets_the_paid_email(): void
    {
        // Arrange — simulate the race by cancelling the order directly in the DB
        // between "apply paid" and "the queued job actually running". Since the
        // queue is synchronous in tests, we fake the queue to capture the job
        // instead of running it immediately, mutate the order, then run it.
        Queue::fake([SendOrderStatusEmailJob::class]);
        Mail::fake();

        $this->postExample('order-paid.json')->assertOk();

        $order = Order::query()->findOrFail('ord_7Q2KX9M4');
        $order->forceFill(['status' => OrderStatus::Cancelled])->save();

        // Act — now actually run the queued job.
        Queue::assertPushed(SendOrderStatusEmailJob::class, function (SendOrderStatusEmailJob $job) {
            app(SendOrderStatusEmailService::class)->handle($job->orderId, $job->type);

            return true;
        });

        // Assert
        Mail::assertNothingSent();
    }
}
