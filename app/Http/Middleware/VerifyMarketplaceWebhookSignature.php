<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Marketplace\VerifyWebhookSignatureAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyMarketplaceWebhookSignature
{
    private const string MISSING_HEADERS_MESSAGE = 'Missing webhook headers.';

    private const string UNSUPPORTED_SIGNATURE_FORMAT_MESSAGE = 'Unsupported signature format.';

    private const string INVALID_SIGNATURE_MESSAGE = 'Invalid webhook signature.';

    public function __construct(
        private VerifyWebhookSignatureAction $verifySignature,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = $request->header('X-Webhook-Timestamp');
        $signature = $request->header('X-Webhook-Signature');

        if ($request->header('X-Webhook-Id') === null || $timestamp === null || $signature === null) {
            return response()->json(
                ['message' => self::MISSING_HEADERS_MESSAGE],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (! str_starts_with($signature, 'sha256=')) {
            return response()->json(
                ['message' => self::UNSUPPORTED_SIGNATURE_FORMAT_MESSAGE],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (! $this->verifySignature->execute($request->getContent(), $timestamp, $signature)) {
            return response()->json(
                ['message' => self::INVALID_SIGNATURE_MESSAGE],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $next($request);
    }
}
