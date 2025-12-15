<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Gateway\Contracts;

use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentResponse;

interface GatewayInterface
{
    public function getName(): string;

    public function getPriority(): int;

    public function supports(string $paymentMethod): bool;

    public function isEnabled(): bool;

    public function process(PaymentRequest $request): PaymentResponse;

    public function getStatus(string $transactionId): PaymentResponse;

    public function refund(string $transactionId, ?int $amountCents = null): PaymentResponse;
}

