<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAquirerCheckout\Domain\Gateway\Contracts\GatewayInterface;
use Kaninstein\MultiAquirerCheckout\Support\Exceptions\PaymentGatewayException;

abstract class AbstractGateway implements GatewayInterface
{
    protected bool $enabled = true;

    protected bool $sandbox = false;

    protected int $timeout = 30;

    protected array $credentials = [];

    public function __construct(array $config = [])
    {
        $this->enabled = (bool) ($config['enabled'] ?? true);
        $this->sandbox = (bool) ($config['sandbox'] ?? false);
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->credentials = (array) ($config['credentials'] ?? []);

        $this->configure($config);
    }

    protected function configure(array $config): void
    {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    abstract protected function getBaseUrl(): string;

    abstract protected function getSandboxUrl(): string;

    protected function getApiUrl(): string
    {
        return $this->sandbox ? $this->getSandboxUrl() : $this->getBaseUrl();
    }

    protected function request(string $method, string $endpoint, array $data = [], array $headers = []): mixed
    {
        $url = $this->getApiUrl().'/'.ltrim($endpoint, '/');

        $channel = (string) config('multi-acquirer.logging.channel', 'stack');
        Log::channel($channel)->info("Gateway request: {$this->getName()}", [
            'method' => $method,
            'url' => $url,
            'data' => $this->sanitizeForLog($data),
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(array_merge($this->getDefaultHeaders(), $headers))
                ->$method($url, $data);

            $result = $response->object() ?? $response->json();

            Log::channel($channel)->debug("Gateway response: {$this->getName()}", [
                'url' => $url,
                'response' => $this->sanitizeForLog($result),
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::channel($channel)->error("Gateway error: {$this->getName()}", [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function normalizeStatus(string $status, string $paymentMethod = ''): string
    {
        $status = strtolower(trim($status));

        if ($paymentMethod === 'pix') {
            return match ($status) {
                'completed', 'paid', 'approved' => 'paid',
                'waiting_payment', 'pending', 'created' => 'pending',
                'failed', 'refused', 'error' => 'failed',
                'refunded', 'charged_back' => 'refunded',
                default => 'pending',
            };
        }

        if ($paymentMethod === 'boleto') {
            return match ($status) {
                'completed', 'paid', 'approved' => 'paid',
                'waiting_payment', 'pending', 'created' => 'pending',
                'expired', 'failed', 'refused' => 'failed',
                'cancelled', 'canceled', 'refunded' => 'canceled',
                default => 'pending',
            };
        }

        return match ($status) {
            'completed', 'paid', 'approved',
            'captured', 'succeeded', 'success' => 'paid',

            'pending', 'waiting', 'in_process',
            'processing', 'created', 'waiting_payment',
            'authorized' => 'pending',

            'failed', 'declined', 'rejected',
            'denied', 'error' => 'failed',

            'charged_back' => 'chargeback',

            'cancelled', 'canceled', 'voided',
            'refunded' => 'canceled',

            default => 'pending',
        };
    }

    protected function sanitizeForLog(mixed $data): mixed
    {
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }

        if (! is_array($data)) {
            return $data;
        }

        $sensitiveFields = [
            'card_number', 'cvv', 'token', 'key', 'secret',
            'password', 'access_token', 'authorization',
            'document', 'email', 'phone', 'api_key', 'secret_key',
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower((string) $key), $sensitiveFields, true)) {
                $value = '[REDACTED]';
            }
        });

        return $data;
    }

    protected function validateRequest(PaymentRequest $request): void
    {
        if ($request->amount->amountInCents <= 0) {
            throw new PaymentGatewayException('Payment amount must be greater than zero.');
        }

        if ($request->isCardPayment() && $request->cardData === null) {
            throw new PaymentGatewayException('Card data is required for card payments.');
        }

        if (! $this->supports($request->paymentMethod->value)) {
            throw new PaymentGatewayException("Payment method '{$request->paymentMethod->value}' is not supported by {$this->getName()}.");
        }
    }
}
