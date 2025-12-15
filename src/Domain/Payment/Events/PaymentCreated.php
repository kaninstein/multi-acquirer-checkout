<?php

namespace Kaninstein\MultiAquirerCheckout\Domain\Payment\Events;

final readonly class PaymentCreated
{
    public function __construct(
        public string $paymentId,
    ) {}
}

