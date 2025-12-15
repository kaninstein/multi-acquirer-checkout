<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts;

use Kaninstein\MultiAquirerCheckout\Domain\Payment\Entities\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function findById(string $id): ?Order;
}

