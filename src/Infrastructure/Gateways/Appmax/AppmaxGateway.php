<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Gateways\Appmax;

use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentResponse;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Gateways\AbstractGateway;

class AppmaxGateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'appmax';
    }

    public function getPriority(): int
    {
        return (int) config('multi-acquirer.gateways.appmax.priority', 2);
    }

    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['card', 'pix', 'boleto'], true);
    }

    public function process(PaymentRequest $request): PaymentResponse
    {
        $this->validateRequest($request);

        return PaymentResponse::failed($this->getName(), 'Appmax gateway integration not configured in this package.');
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
        return 'https://admin.appmax.com.br/api/v3';
    }

    protected function getSandboxUrl(): string
    {
        return 'https://homolog.sandboxappmax.com.br/api/v3';
    }
}

