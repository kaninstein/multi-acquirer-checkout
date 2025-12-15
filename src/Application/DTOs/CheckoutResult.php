<?php

namespace Kaninstein\MultiAcquirerCheckout\Application\DTOs;

use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities\Payment;

final readonly class CheckoutResult
{
    public function __construct(
        public bool $success,
        public Payment $payment,
        public FeeCalculationResult $fees,
        public PaymentResponse $gatewayResponse,
    ) {}
}

