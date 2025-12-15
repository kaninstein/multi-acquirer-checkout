<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts;

use Kaninstein\MultiAquirerCheckout\Domain\Payment\Entities\Payment;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function findById(string $id): ?Payment;
}

