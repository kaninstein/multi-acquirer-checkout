<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events;

final readonly class PaymentCreated
{
    public function __construct(
        public string $paymentId,
    ) {}
}

