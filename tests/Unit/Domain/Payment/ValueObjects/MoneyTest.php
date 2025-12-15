<?php

namespace Kaninstein\MultiAquirerCheckout\Tests\Unit\Domain\Payment\ValueObjects;

use InvalidArgumentException;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAquirerCheckout\Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(-1);
    }

    public function test_it_adds_money_with_same_currency(): void
    {
        $a = Money::fromCents(100, 'BRL');
        $b = Money::fromCents(250, 'BRL');

        $c = $a->add($b);

        $this->assertSame(350, $c->amountInCents);
        $this->assertSame('BRL', $c->currency);
    }

    public function test_it_rejects_currency_mismatch_on_add(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(100, 'BRL')->add(Money::fromCents(100, 'USD'));
    }
}

