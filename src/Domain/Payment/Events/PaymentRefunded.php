<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events;

final readonly class PaymentRefunded
{
    public function __construct(
        public string $paymentId,
        public ?string $gatewayTransactionId = null,
    ) {}
}

