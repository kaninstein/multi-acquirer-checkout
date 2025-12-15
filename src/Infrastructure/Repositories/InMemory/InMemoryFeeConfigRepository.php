<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\InMemory;

use Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts\FeeConfigRepositoryInterface;

class InMemoryFeeConfigRepository implements FeeConfigRepositoryInterface
{
    public function getFeeFor(string $gatewayName, string $paymentMethod, int $installments): ?array
    {
        return null;
    }
}
