<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events;

final readonly class PaymentFailed
{
    public function __construct(
        public string $paymentId,
        public string $reason,
    ) {}
}

