<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Gateways\Pagarme;

use Kaninstein\LaravelPagarme\Exceptions\BadRequestException;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;
use Kaninstein\LaravelPagarme\Exceptions\ValidationException;
use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentResponse;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Gateways\AbstractGateway;
use Kaninstein\MultiAcquirerCheckout\Support\Exceptions\CardRejectionException;
use Kaninstein\MultiAcquirerCheckout\Support\Exceptions\PaymentGatewayException;

class PagarmeGateway extends AbstractGateway
{
    protected string $secretKey = '';

    protected string $publicKey = '';

    protected ?string $accountId = null;

    public function getName(): string
    {
        return 'pagarme';
    }

    public function getPriority(): int
    {
        return (int) config('multi-acquirer.gateways.pagarme.priority', 1);
    }

    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['card', 'pix', 'boleto'], true);
    }

    public function process(PaymentRequest $request): PaymentResponse
    {
        $this->validateRequest($request);

        $this->assertIntegrationAvailable();
        $this->configureFacade();

        try {
            $order = Pagarme::orders()->create($this->buildOrderData($request));

            $charge = is_array($order) ? ($order['charges'][0] ?? null) : null;
            if (! is_array($charge)) {
                throw new PaymentGatewayException('No charge returned from Pagar.me.');
            }

            $lastTransaction = is_array($charge['last_transaction'] ?? null) ? $charge['last_transaction'] : [];
            $gatewayResponse = is_array($lastTransaction['gateway_response'] ?? null) ? $lastTransaction['gateway_response'] : [];
            $gatewayErrors = is_array($gatewayResponse['errors'] ?? null) ? $gatewayResponse['errors'] : [];

            $txId = (string) ($charge['id'] ?? $order['id'] ?? '');
            $status = $this->normalizeStatus((string) ($charge['status'] ?? $order['status'] ?? 'pending'), $request->paymentMethod->value);

            $payload = [
                'id' => $txId,
                'original_status' => (string) ($charge['status'] ?? $order['status'] ?? ''),
                'amount_cents' => (int) ($charge['amount'] ?? $order['amount'] ?? $request->amount->amountInCents),
                'currency' => (string) ($order['currency'] ?? $request->amount->currency),
                'payment_method' => $request->paymentMethod->value,
                'metadata' => [
                    'order_id' => $order['id'] ?? null,
                    'charge_id' => $charge['id'] ?? null,
                    'transaction_id' => $lastTransaction['id'] ?? null,
                ],
            ];

            if ($gatewayErrors !== []) {
                $payload['metadata']['gateway_errors'] = $gatewayErrors;
                $payload['metadata']['gateway_code'] = $gatewayResponse['code'] ?? null;
            }

            if ($request->paymentMethod->value === 'pix') {
                [$pix, $pixUrl] = $this->resolvePixData($lastTransaction, $charge);

                // Get expiration time from charge or set default (1 hour from now)
                $expiresAt = null;
                if (isset($lastTransaction['pix_expiration_date'])) {
                    $expiresAt = $lastTransaction['pix_expiration_date'];
                } elseif (isset($charge['metadata']['pix_expiration_date'])) {
                    $expiresAt = $charge['metadata']['pix_expiration_date'];
                } else {
                    // Default to 1 hour from now if not specified
                    $expiresAt = gmdate('c', strtotime('+1 hour'));
                }

                $payload['pix'] = [
                    'qrcode' => $pix,
                    'copy_paste' => $pix,
                    'qrcode_url' => $pixUrl,
                    'expires_at' => $expiresAt,
                ];
            }

            if ($request->paymentMethod->value === 'boleto') {
                $payload['boleto'] = [
                    'barcode' => $lastTransaction['line'] ?? null,
                    'url' => $lastTransaction['url'] ?? $lastTransaction['pdf'] ?? null,
                ];
            }

            if ($status === 'paid') {
                return PaymentResponse::success($this->getName(), $payload);
            }

            if ($status === 'failed') {
                $acquirerMessage = is_string($lastTransaction['acquirer_message'] ?? null) ? $lastTransaction['acquirer_message'] : null;
                $errorMessage = 'Payment not approved';

                if ($gatewayErrors !== []) {
                    $first = $gatewayErrors[0]['message'] ?? null;
                    if (is_string($first) && $first !== '') {
                        $errorMessage = $first;
                    }
                } elseif (is_string($acquirerMessage) && $acquirerMessage !== '') {
                    $errorMessage = $acquirerMessage;
                }

                return PaymentResponse::failed($this->getName(), $errorMessage, null, $payload);
            }

            // For simulator scenarios (ex.: "with_error"), Pagar.me may return gateway errors even when the charge is still processing.
            // In that case, keep the payment as pending and let webhooks update the final status.
            if (
                $request->paymentMethod->value === 'card'
                && $gatewayErrors !== []
                && in_array((string) ($lastTransaction['status'] ?? ''), ['with_error', 'processing'], true)
            ) {
                return PaymentResponse::pending($this->getName(), $payload);
            }

            return PaymentResponse::pending($this->getName(), $payload);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $message = $e->getMessage();
            if (is_array($errors) && $errors !== []) {
                $message .= ' - '.json_encode($errors);
            }

            if ($this->isCardRejectionError($message)) {
                throw new CardRejectionException($message, previous: $e);
            }

            return PaymentResponse::failed($this->getName(), $message);
        } catch (BadRequestException $e) {
            $message = $e->getMessage();
            if ($this->isCardRejectionError($message)) {
                throw new CardRejectionException($message, previous: $e);
            }

            return PaymentResponse::failed($this->getName(), $message);
        } catch (PagarmeException $e) {
            $message = $e->getMessage();
            if ($this->isCardRejectionError($message)) {
                throw new CardRejectionException($message, previous: $e);
            }

            return PaymentResponse::failed($this->getName(), $message);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if ($this->isCardRejectionError($message)) {
                throw new CardRejectionException($message, previous: $e);
            }

            return PaymentResponse::failed($this->getName(), $message);
        }
    }

    public function getStatus(string $transactionId): PaymentResponse
    {
        $this->assertIntegrationAvailable();

        return PaymentResponse::failed($this->getName(), 'Status lookup not implemented.');
    }

    public function refund(string $transactionId, ?int $amountCents = null): PaymentResponse
    {
        $this->assertIntegrationAvailable();

        return PaymentResponse::failed($this->getName(), 'Refund not implemented.');
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function configure(array $config): void
    {
        $this->secretKey = (string) ($config['secret_key'] ?? '');
        $this->publicKey = (string) ($config['public_key'] ?? '');
        $this->accountId = isset($config['account_id']) ? (string) $config['account_id'] : null;
    }

    protected function getBaseUrl(): string
    {
        return 'https://api.pagar.me/core/v5';
    }

    protected function getSandboxUrl(): string
    {
        return 'https://api.pagar.me/core/v5';
    }

    private function assertIntegrationAvailable(): void
    {
        if (! class_exists(Pagarme::class)) {
            throw new PaymentGatewayException('Missing dependency: kaninstein/laravel-pagarme.');
        }
    }

    private function configureFacade(): void
    {
        if ($this->secretKey !== '' && ! config('pagarme.secret_key')) {
            config(['pagarme.secret_key' => $this->secretKey]);
        }

        if ($this->publicKey !== '' && ! config('pagarme.public_key')) {
            config(['pagarme.public_key' => $this->publicKey]);
        }

        if ($this->accountId !== null && ! config('pagarme.account_id')) {
            config(['pagarme.account_id' => $this->accountId]);
        }
    }

/**
     * @return array<string, mixed>
     */
        private function buildOrderData(PaymentRequest $request): array
    {
        $order = [
            'items' => [
                [
                    'amount' => $request->amount->amountInCents,
                    'description' => (string) ($request->metadata['description'] ?? 'Checkout'),
                    'quantity' => 1,
                    'code' => (string) ($request->metadata['item_code'] ?? 'ITEM-1'),
                ],
            ],
            'customer' => $this->buildCustomerData($request),
            'payments' => [
                $this->buildPaymentData($request),
            ],
        ];

        $metadata = (array) ($request->metadata ?? []);
        if ($metadata !== []) {
            $order['metadata'] = $metadata;
        }

        return $order;
    }

/**
     * @return array<string, mixed>
     */
        private function buildCustomerData(PaymentRequest $request): array
    {
        $customer = [
            'name' => $request->customer->name,
            'email' => $request->customer->email,
            'type' => 'individual',
        ];

        if ($request->customer->document) {
            $document = preg_replace('/\\D/', '', $request->customer->document);
            $customer['document'] = $document;
            $customer['document_type'] = strlen((string) $document) === 11 ? 'CPF' : 'CNPJ';
        }

        $phonePayload = $this->buildPhonePayload($request->customer->phone ?? null);
        if ($phonePayload) {
            $customer['phones'] = [
                'home_phone' => $phonePayload,
            ];
        }

        return $customer;
    }

/**
     * @return array<string, mixed>
     */
        private function buildPaymentData(PaymentRequest $request): array
    {
        return match ($request->paymentMethod->value) {
            'pix' => [
                'payment_method' => 'pix',
                'pix' => [
                    'expires_in' => (int) config('multi-acquirer.payment_methods.pix.expiration_seconds', 3600),
                ],
            ],
            'boleto' => [
                'payment_method' => 'boleto',
                'boleto' => [
                    'instructions' => (string) config('multi-acquirer.gateways.pagarme.boleto_instructions', ''),
                    'due_at' => gmdate('c', strtotime('+'.(int) config('multi-acquirer.payment_methods.boleto.due_days', 3).' days') ?: null),
                ],
            ],
            default => $this->buildCardPaymentData($request),
        };
    }

/**
     * @return array<string, mixed>
     */
        private function buildCardPaymentData(PaymentRequest $request): array
    {
        $card = $request->cardData;
        if (! $card) {
            throw new PaymentGatewayException('Card data is required.');
        }

        $statement = (string) config('multi-acquirer.gateways.pagarme.statement_descriptor', '');

        $payload = [
            'payment_method' => 'credit_card',
            'credit_card' => [
                'installments' => max(1, min(12, $request->installments)),
            ],
        ];

        if (is_string($card->token) && $card->token !== '') {
            $payload['credit_card']['card_id'] = $card->token;
        } else {
            $payload['credit_card']['card'] = [
                'number' => $card->number,
                'holder_name' => $card->holderName,
                'exp_month' => $card->expMonth,
                'exp_year' => $card->expYear,
                'cvv' => $card->cvv,
            ];
        }

        if ($statement !== '') {
            $payload['credit_card']['statement_descriptor'] = $statement;
        }

        return $payload;
    }

    private function isCardRejectionError(string $errorMessage): bool
    {
        $patterns = [
            'card declined',
            'insufficient funds',
            'transaction not authorized',
            'issuer declined',
            'rejected by issuer',
            'cartão rejeitado',
            'cartão bloqueado',
            'limit exceeded',
            'invalid card',
            'expired card',
            'transação recusada',
            'não autorizada',
        ];

        $errorLower = strtolower($errorMessage);

        foreach ($patterns as $pattern) {
            if (str_contains($errorLower, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>|null
     */
    private function buildPhonePayload(?string $phone): ?array
    {
        if (! is_string($phone) || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\\D/', '', $phone);
        if ($digits === '') {
            return null;
        }

        $countryCode = '55';
        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            $countryCode = substr($digits, 0, 2);
            $areaCode = substr($digits, 2, 2);
            $number = substr($digits, 4);
        } elseif (strlen($digits) >= 10) {
            $areaCode = substr($digits, 0, 2);
            $number = substr($digits, 2);
        } else {
            return null;
        }

        if (strlen($number) < 8) {
            return null;
        }

        return [
            'country_code' => $countryCode,
            'area_code' => $areaCode,
            'number' => $number,
        ];
    }

    /**
     * @param  array<string, mixed>  $lastTransaction
     * @param  array<string, mixed>  $charge
     * @return array{0:?string,1:?string}
     */
    private function resolvePixData(array $lastTransaction, array $charge): array
    {
        \Illuminate\Support\Facades\Log::debug('Pagarme: Resolving PIX data', [
            'transaction_keys' => array_keys($lastTransaction),
            'charge_keys' => array_keys($charge),
        ]);

        $pixCode = $this->firstNonUrl([
            $lastTransaction['pix_qr_code'] ?? null,
            $lastTransaction['qr_code'] ?? null,
            $lastTransaction['pix_copy_paste'] ?? null,
        ]);

        $pixUrl = $this->firstUrl([
            $lastTransaction['pix_qr_code_url'] ?? null,
            $lastTransaction['qr_code_url'] ?? null,
        ]);

        \Illuminate\Support\Facades\Log::debug('Pagarme: Initial PIX data from transaction', [
            'has_pix_code' => $pixCode !== null,
            'has_pix_url' => $pixUrl !== null,
        ]);

        if ($pixCode === null) {
            $pixCode = $this->firstNonUrl([
                $lastTransaction['pix_qr_code_url'] ?? null,
                $lastTransaction['qr_code_url'] ?? null,
            ]);
        }

        if ($pixCode === null) {
            $transactionId = $lastTransaction['id'] ?? $charge['id'] ?? null;
            if ($transactionId) {
                \Illuminate\Support\Facades\Log::debug('Pagarme: Fetching QR code from API', [
                    'transaction_id' => $transactionId,
                ]);

                try {
                    $qr = Pagarme::transactions()->qrcode((string) $transactionId, 'pix');

                    \Illuminate\Support\Facades\Log::debug('Pagarme: QR code API response', [
                        'qr_response_keys' => array_keys($qr),
                        'qr_response' => $qr,
                    ]);

                    $pixCode = $this->firstNonUrl([
                        $qr['qr_code'] ?? null,
                        $qr['pix_qr_code'] ?? null,
                        $qr['emv'] ?? null,
                        $qr['payload'] ?? null,
                    ]);
                    if ($pixUrl === null) {
                        $pixUrl = $this->firstUrl([
                            $qr['qr_code_url'] ?? null,
                            $qr['pix_qr_code_url'] ?? null,
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Pagarme: Failed to fetch QR code', [
                        'transaction_id' => $transactionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        \Illuminate\Support\Facades\Log::debug('Pagarme: Final PIX data', [
            'has_pix_code' => $pixCode !== null,
            'has_pix_url' => $pixUrl !== null,
            'pix_code_length' => $pixCode ? strlen($pixCode) : 0,
        ]);

        return [$pixCode, $pixUrl];
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function firstNonUrl(array $values): ?string
    {
        foreach ($values as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            if (! $this->looksLikeUrl($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function firstUrl(array $values): ?string
    {
        foreach ($values as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($this->looksLikeUrl($value)) {
                return $value;
            }
        }

        return null;
    }

    private function looksLikeUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }
}
