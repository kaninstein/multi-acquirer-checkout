<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities;

use Illuminate\Support\Str;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;

class Order
{
    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $metadata
     */
    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $id,
        public readonly Money $amount,
        public readonly Customer $customer,
        public array $items = [],
        public array $metadata = [],
    ) {}

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $metadata
     */
    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $metadata
     */
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

    /**
     * @return array<string, mixed>
     */
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

