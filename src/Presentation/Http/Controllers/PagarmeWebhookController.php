<?php

namespace Kaninstein\MultiAcquirerCheckout\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kaninstein\LaravelPagarme\Services\WebhookValidator;
use Kaninstein\MultiAcquirerCheckout\Application\Services\PagarmeWebhookService;

final readonly class PagarmeWebhookController
{
    public function __construct(
        private PagarmeWebhookService $service,
        private WebhookValidator $validator,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();

        if ((bool) config('multi-acquirer.webhooks.validate_signature', true)) {
            $result = $this->validator->validateWebhook($request);
            $valid = (bool) ($result['valid'] ?? false);

            if (! $valid) {
                Log::warning('Invalid Pagarme webhook signature', [
                    'reasons' => $result['reasons'] ?? [],
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Invalid webhook'], 401);
            }
        }

        $result = $this->service->handle($payload);

        return response()->json($result, 200);
    }
}

