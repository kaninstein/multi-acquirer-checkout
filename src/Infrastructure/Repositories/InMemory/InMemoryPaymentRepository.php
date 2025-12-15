<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\InMemory;

use Kaninstein\MultiAquirerCheckout\Domain\Payment\Entities\Payment;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\PaymentRepositoryInterface;

class InMemoryPaymentRepository implements PaymentRepositoryInterface
{
    /** @var array<string, Payment> */
    private array $payments = [];

    public function save(Payment $payment): void
    {
        $this->payments[$payment->id] = $payment;
    }

    public function findById(string $id): ?Payment
    {
        return $this->payments[$id] ?? null;
    }
}

