<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\Stripe;

use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentResponse;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\AbstractGateway;

class StripeGateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'stripe';
    }

    public function getPriority(): int
    {
        return (int) config('multi-acquirer.gateways.stripe.priority', 3);
    }

    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['card', 'pix', 'boleto'], true);
    }

    public function process(PaymentRequest $request): PaymentResponse
    {
        $this->validateRequest($request);

        return PaymentResponse::failed($this->getName(), 'Stripe gateway integration not configured in this package.');
    }

    public function getStatus(string $transactionId): PaymentResponse
    {
        return PaymentResponse::failed($this->getName(), 'Status lookup not implemented.');
    }

    public function refund(string $transactionId, ?int $amountCents = null): PaymentResponse
    {
        return PaymentResponse::failed($this->getName(), 'Refund not implemented.');
    }

    protected function getBaseUrl(): string
    {
        return 'https://api.stripe.com';
    }

    protected function getSandboxUrl(): string
    {
        return 'https://api.stripe.com';
    }
}

