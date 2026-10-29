<?php

namespace App\Actions\Marketplace;

/**
 * Verifies a MarketHub webhook signature: HMAC-SHA256 of "{timestamp}.{raw body}"
 * with the seller's secret, and rejects timestamps too far from now.
 *
 * @see /task/marketplace-webhooks.md
 */
final class VerifyWebhookSignatureAction
{
    public function __invoke(string $rawBody, string $timestamp, string $signature): bool
    {
        if (! ctype_digit($timestamp)) {
            return false;
        }

        $tolerance = (int) config('services.marketplace.webhook_tolerance', 300);

        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return false;
        }

        $secret = (string) config('services.marketplace.webhook_secret');

        if ($secret === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
