<?php

namespace Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case REFUNDED = 'refunded';

    public function isSuccessful(): bool
    {
        return $this === self::PAID || $this === self::AUTHORIZED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::PAID, self::FAILED, self::CANCELED, self::REFUNDED], true);
    }
}

