<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Eloquent;

use Kaninstein\MultiAquirerCheckout\Domain\Payment\Entities\Order;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Persistence\Models\OrderModel;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\OrderRepositoryInterface;

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

