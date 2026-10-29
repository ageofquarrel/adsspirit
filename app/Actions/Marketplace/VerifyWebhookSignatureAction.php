<?php

declare(strict_types=1);

namespace App\Actions\Marketplace;

/**
 * Проверяет подпись вебхука MarketHub: HMAC-SHA256 от строки
 * "{timestamp}.{тело запроса}" с секретом продавца, отклоняет запросы
 * со слишком старым timestamp.
 *
 * @see /task/marketplace-webhooks.md
 */
final class VerifyWebhookSignatureAction
{
    public function execute(string $rawBody, string $timestamp, string $signature): bool
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
