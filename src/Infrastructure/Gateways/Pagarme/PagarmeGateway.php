<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\Pagarme;

use Kaninstein\LaravelPagarme\Exceptions\BadRequestException;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;
use Kaninstein\LaravelPagarme\Exceptions\ValidationException;
use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentResponse;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\AbstractGateway;
use Kaninstein\MultiAquirerCheckout\Support\Exceptions\CardRejectionException;
use Kaninstein\MultiAquirerCheckout\Support\Exceptions\PaymentGatewayException;

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
                ],
            ];

            if ($request->paymentMethod->value === 'pix') {
                $pix = $charge['last_transaction']['qr_code'] ?? $charge['last_transaction']['pix_qr_code'] ?? null;
                $pixText = $charge['last_transaction']['qr_code_url'] ?? $charge['last_transaction']['pix_qr_code_url'] ?? null;
                $payload['pix'] = [
                    'qrcode' => $pix,
                    'copy_paste' => $pixText,
                ];
            }

            if ($request->paymentMethod->value === 'boleto') {
                $payload['boleto'] = [
                    'barcode' => $charge['last_transaction']['line'] ?? null,
                    'url' => $charge['last_transaction']['url'] ?? null,
                ];
            }

            if ($status === 'paid') {
                return PaymentResponse::success($this->getName(), $payload);
            }

            if ($status === 'failed') {
                return PaymentResponse::failed($this->getName(), 'Payment not approved', null, $payload);
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

        return $customer;
    }

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
                    'due_at' => gmdate('c', strtotime('+'.(int) config('multi-acquirer.payment_methods.boleto.due_days', 3).' days')),
                ],
            ],
            default => $this->buildCardPaymentData($request),
        };
    }

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
                'card' => [
                    'number' => $card->number,
                    'holder_name' => $card->holderName,
                    'exp_month' => $card->expMonth,
                    'exp_year' => $card->expYear,
                    'cvv' => $card->cvv,
                ],
            ],
        ];

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
}
