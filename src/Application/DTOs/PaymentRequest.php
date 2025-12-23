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
     * @param array<int, array<string, mixed>> $items
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
        public array $items = [],
    ) {}

    public function isCardPayment(): bool
    {
        return $this->paymentMethod === PaymentMethod::CARD;
    }
}
