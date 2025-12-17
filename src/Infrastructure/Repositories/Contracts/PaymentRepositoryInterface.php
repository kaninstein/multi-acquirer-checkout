<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts;

use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities\Payment;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function findById(string $id): ?Payment;

    public function findByGatewayTransactionId(string $gatewayTransactionId): ?Payment;
}
