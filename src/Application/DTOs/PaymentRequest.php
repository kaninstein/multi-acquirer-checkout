<?php

namespace Kaninstein\MultiAquirerCheckout\Application\DTOs;

use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\CardData;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\PaymentMethod;

final readonly class PaymentRequest
{
    public function __construct(
        public Money $amount,
        public PaymentMethod $paymentMethod,
        public int $installments,
        public Customer $customer,
        public ?CardData $cardData = null,
        public array $metadata = [],
        public string $preferredGateway = '',
        public bool $merchantAbsorbsFinancing = false,
        public string $feeResponsibility = 'buyer',
    ) {}

    public function isCardPayment(): bool
    {
        return $this->paymentMethod === PaymentMethod::CARD;
    }
}

