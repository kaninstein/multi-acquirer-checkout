<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities;

use Illuminate\Support\Str;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;

class Order
{
    public function __construct(
        public readonly string $id,
        public readonly Money $amount,
        public readonly Customer $customer,
        public array $items = [],
        public array $metadata = [],
    ) {}

    public static function create(Money $amount, Customer $customer, array $items = [], array $metadata = []): self
    {
        return new self(
            id: (string) Str::uuid(),
            amount: $amount,
            customer: $customer,
            items: $items,
            metadata: $metadata,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount_cents' => $this->amount->amountInCents,
            'currency' => $this->amount->currency,
            'customer' => $this->customer->toArray(),
            'items' => $this->items,
            'metadata' => $this->metadata,
        ];
    }
}

