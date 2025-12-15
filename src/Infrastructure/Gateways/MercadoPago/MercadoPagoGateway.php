<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\MercadoPago;

use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentResponse;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\AbstractGateway;

class MercadoPagoGateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'mercadopago';
    }

    public function getPriority(): int
    {
        return (int) config('multi-acquirer.gateways.mercadopago.priority', 4);
    }

    public function supports(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['card', 'pix', 'boleto'], true);
    }

    public function process(PaymentRequest $request): PaymentResponse
    {
        $this->validateRequest($request);

        return PaymentResponse::failed($this->getName(), 'Mercado Pago gateway integration not configured in this package.');
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
        return 'https://api.mercadopago.com';
    }

    protected function getSandboxUrl(): string
    {
        return 'https://api.mercadopago.com';
    }
}

