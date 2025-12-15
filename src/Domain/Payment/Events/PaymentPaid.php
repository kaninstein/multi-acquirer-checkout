<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events;

final readonly class PaymentPaid
{
    public function __construct(
        public string $paymentId,
    ) {}
}

