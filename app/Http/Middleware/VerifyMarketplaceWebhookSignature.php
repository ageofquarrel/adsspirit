<?php

namespace App\Http\Middleware;

use App\Actions\Marketplace\VerifyWebhookSignatureAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyMarketplaceWebhookSignature
{
    public function __construct(
        private readonly VerifyWebhookSignatureAction $verifySignature,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = $request->header('X-Webhook-Timestamp');
        $signature = $request->header('X-Webhook-Signature');

        if ($request->header('X-Webhook-Id') === null || $timestamp === null || $signature === null) {
            return response()->json(['message' => 'Missing webhook headers.'], 400);
        }

        if (! str_starts_with($signature, 'sha256=')) {
            return response()->json(['message' => 'Unsupported signature format.'], 400);
        }

        if (! ($this->verifySignature)($request->getContent(), $timestamp, $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        return $next($request);
    }
}
