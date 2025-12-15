<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities;

use Illuminate\Support\Str;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events\PaymentAuthorized;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events\PaymentCreated;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events\PaymentFailed;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Events\PaymentPaid;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\PaymentMethod;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\PaymentStatus;
use Kaninstein\MultiAcquirerCheckout\Support\Traits\HasDomainEvents;

class Payment
{
    use HasDomainEvents;

    public function __construct(
        public readonly string $id,
        public readonly Money $amount,
        public readonly PaymentMethod $method,
        public Customer $customer,
        public PaymentStatus $status = PaymentStatus::PENDING,
        public array $metadata = [],
        public ?string $gatewayTransactionId = null,
        public ?string $failureReason = null,
    ) {}

    public static function create(
        Money $amount,
        PaymentMethod $method,
        Customer $customer,
        array $metadata = [],
    ): self {
        $payment = new self(
            id: (string) Str::uuid(),
            amount: $amount,
            method: $method,
            customer: $customer,
            status: PaymentStatus::PENDING,
            metadata: $metadata,
        );

        $payment->recordEvent(new PaymentCreated($payment->id));

        return $payment;
    }

    public function authorize(string $gatewayTransactionId): void
    {
        $this->gatewayTransactionId = $gatewayTransactionId;
        $this->status = PaymentStatus::AUTHORIZED;
        $this->recordEvent(new PaymentAuthorized($this->id, $gatewayTransactionId));
    }

    public function markPaid(): void
    {
        $this->status = PaymentStatus::PAID;
        $this->recordEvent(new PaymentPaid($this->id));
    }

    public function fail(string $reason): void
    {
        $this->status = PaymentStatus::FAILED;
        $this->failureReason = $reason;
        $this->recordEvent(new PaymentFailed($this->id, $reason));
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount_cents' => $this->amount->amountInCents,
            'currency' => $this->amount->currency,
            'payment_method' => $this->method->value,
            'status' => $this->status->value,
            'gateway_transaction_id' => $this->gatewayTransactionId,
            'failure_reason' => $this->failureReason,
            'customer' => $this->customer->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}

