<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events;

final readonly class PaymentAuthorized
{
    public function __construct(
        public string $paymentId,
        public string $gatewayTransactionId,
    ) {}
}

