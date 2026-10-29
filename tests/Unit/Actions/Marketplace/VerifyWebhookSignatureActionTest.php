<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Marketplace;

use App\Actions\Marketplace\VerifyWebhookSignatureAction;
use Carbon\Carbon;
use Tests\TestCase;

final class VerifyWebhookSignatureActionTest extends TestCase
{
    private VerifyWebhookSignatureAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new VerifyWebhookSignatureAction;
    }

    public function test_it_accepts_the_documented_signature_example(): void
    {
        config(['services.marketplace.webhook_secret' => 'whsec_test_5f1d8c2a9b']);
        $this->travelTo(Carbon::createFromTimestamp(1792577149, 'UTC'));
        $body = file_get_contents(base_path('task/examples/signature-example-body.json'));

        $result = $this->action->execute(
            $body,
            '1792577149',
            'sha256=4d4f2d50f98596ab503d29dc96db6fda247e3c482bff130bd97353564cb163f7',
        );

        $this->assertTrue($result);
    }

    public function test_it_rejects_a_signature_made_with_the_wrong_secret(): void
    {
        config(['services.marketplace.webhook_secret' => 'a-different-secret']);
        $this->travelTo(Carbon::createFromTimestamp(1792577149, 'UTC'));
        $body = file_get_contents(base_path('task/examples/signature-example-body.json'));

        $result = $this->action->execute(
            $body,
            '1792577149',
            'sha256=4d4f2d50f98596ab503d29dc96db6fda247e3c482bff130bd97353564cb163f7',
        );

        $this->assertFalse($result);
    }

    public function test_it_rejects_a_tampered_body(): void
    {
        config(['services.marketplace.webhook_secret' => 'whsec_test_5f1d8c2a9b']);
        $this->travelTo(Carbon::createFromTimestamp(1792577149, 'UTC'));

        $result = $this->action->execute(
            '{"tampered":true}',
            '1792577149',
            'sha256=4d4f2d50f98596ab503d29dc96db6fda247e3c482bff130bd97353564cb163f7',
        );

        $this->assertFalse($result);
    }

    public function test_it_rejects_a_timestamp_older_than_the_tolerance(): void
    {
        config([
            'services.marketplace.webhook_secret' => 'secret',
            'services.marketplace.webhook_tolerance' => 300,
        ]);
        $this->travelTo(Carbon::now());
        $timestamp = (string) now()->subMinutes(10)->timestamp;
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.{}', 'secret');

        $result = $this->action->execute('{}', $timestamp, $signature);

        $this->assertFalse($result);
    }

    public function test_it_accepts_a_timestamp_within_the_tolerance(): void
    {
        config([
            'services.marketplace.webhook_secret' => 'secret',
            'services.marketplace.webhook_tolerance' => 300,
        ]);
        $this->travelTo(Carbon::now());
        $timestamp = (string) now()->subMinutes(4)->timestamp;
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.{}', 'secret');

        $result = $this->action->execute('{}', $timestamp, $signature);

        $this->assertTrue($result);
    }

    public function test_it_rejects_a_non_numeric_timestamp(): void
    {
        config(['services.marketplace.webhook_secret' => 'secret']);

        $result = $this->action->execute('{}', 'not-a-number', 'sha256=anything');

        $this->assertFalse($result);
    }
}
