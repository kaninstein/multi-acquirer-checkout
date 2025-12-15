<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Eloquent;

use Kaninstein\MultiAquirerCheckout\Domain\Payment\Entities\Payment;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\PaymentMethod;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\ValueObjects\PaymentStatus;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Persistence\Models\PaymentModel;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\PaymentRepositoryInterface;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private readonly PaymentModel $model,
    ) {}

    public function save(Payment $payment): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['id' => $payment->id],
            [
                'amount_cents' => $payment->amount->amountInCents,
                'currency' => $payment->amount->currency,
                'status' => $payment->status->value,
                'payment_method' => $payment->method->value,
                'customer_data' => $payment->customer->toArray(),
                'gateway_transaction_id' => $payment->gatewayTransactionId,
                'failure_reason' => $payment->failureReason,
                'metadata' => $payment->metadata,
            ]
        );
    }

    public function findById(string $id): ?Payment
    {
        /** @var PaymentModel|null $model */
        $model = $this->model->newQuery()->find($id);
        if (! $model) {
            return null;
        }

        return new Payment(
            id: (string) $model->id,
            amount: Money::fromCents((int) $model->amount_cents, (string) $model->currency),
            method: PaymentMethod::from((string) $model->payment_method),
            customer: Customer::fromArray((array) ($model->customer_data ?? [])),
            status: PaymentStatus::from((string) $model->status),
            metadata: (array) ($model->metadata ?? []),
            gatewayTransactionId: $model->gateway_transaction_id ? (string) $model->gateway_transaction_id : null,
            failureReason: $model->failure_reason ? (string) $model->failure_reason : null,
        );
    }
}

