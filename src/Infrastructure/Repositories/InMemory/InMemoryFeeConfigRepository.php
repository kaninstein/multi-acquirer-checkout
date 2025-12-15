<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\InMemory;

use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\FeeConfigRepositoryInterface;

class InMemoryFeeConfigRepository implements FeeConfigRepositoryInterface
{
    public function getFeeFor(string $gatewayName, string $paymentMethod, int $installments): ?array
    {
        return null;
    }
}
