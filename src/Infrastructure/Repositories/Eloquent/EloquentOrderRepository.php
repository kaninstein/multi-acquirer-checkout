<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Eloquent;

use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities\Order;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Persistence\Models\OrderModel;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts\OrderRepositoryInterface;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly OrderModel $model,
    ) {}

    public function save(Order $order): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['id' => $order->id],
            [
                'amount_cents' => $order->amount->amountInCents,
                'currency' => $order->amount->currency,
                'customer_data' => $order->customer->toArray(),
                'metadata' => $order->metadata,
            ]
        );
    }

    public function findById(string $id): ?Order
    {
        /** @var OrderModel|null $model */
        $model = $this->model->newQuery()->find($id);
        if (! $model) {
            return null;
        }

        return new Order(
            id: (string) $model->id,
            amount: Money::fromCents((int) $model->amount_cents, (string) $model->currency),
            customer: Customer::fromArray((array) ($model->customer_data ?? [])),
            items: [],
            metadata: (array) ($model->metadata ?? []),
        );
    }
}

