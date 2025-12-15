<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\InMemory;

use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities\Order;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts\OrderRepositoryInterface;

class InMemoryOrderRepository implements OrderRepositoryInterface
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[$order->id] = $order;
    }

    public function findById(string $id): ?Order
    {
        return $this->orders[$id] ?? null;
    }
}

