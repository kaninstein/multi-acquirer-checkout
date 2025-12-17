<?php

namespace Kaninstein\MultiAcquirerCheckout\Application\DTOs;

use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\CardData;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\PaymentMethod;

final readonly class PaymentRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
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
        public ?float $platformFeeRate = null,
    ) {}

    public function isCardPayment(): bool
    {
        return $this->paymentMethod === PaymentMethod::CARD;
    }
}
