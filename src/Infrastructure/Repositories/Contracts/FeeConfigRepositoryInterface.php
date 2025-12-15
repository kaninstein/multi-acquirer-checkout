<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts;

interface FeeConfigRepositoryInterface
{
    /**
     * @return array{percentage: float, fixed_cents: int}|null
     */
    public function getFeeFor(string $gatewayName, string $paymentMethod, int $installments): ?array;
}

