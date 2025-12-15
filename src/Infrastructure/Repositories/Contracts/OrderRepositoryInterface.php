<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts;

use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function findById(string $id): ?Order;
}

