<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events;

final readonly class PaymentCanceled
{
    public function __construct(
        public string $paymentId,
        public ?string $gatewayTransactionId = null,
    ) {}
}

