<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    private function __construct(
        public int $amountInCents,
        public string $currency = 'BRL',
    ) {
        if ($amountInCents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }

        $currency = strtoupper($currency);
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be a 3-letter ISO code.');
        }
    }

    public static function fromCents(int $cents, string $currency = 'BRL'): self
    {
        return new self($cents, $currency);
    }

    public static function fromDecimal(float $amount, string $currency = 'BRL'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amountInCents - $other->amountInCents, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        $result = bcmul((string) $this->amountInCents, (string) $multiplier, 0);

        return new self((int) $result, $this->currency);
    }

    public function toDecimal(): float
    {
        return $this->amountInCents / 100;
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currency mismatch.');
        }
    }
}

