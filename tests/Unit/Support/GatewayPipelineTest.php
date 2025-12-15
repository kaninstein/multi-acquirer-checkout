<?php

namespace Kaninstein\MultiAcquirerCheckout\Tests\Unit\Support;

use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentResponse;
use Kaninstein\MultiAcquirerCheckout\Domain\Gateway\Contracts\GatewayInterface;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\PaymentMethod;
use Kaninstein\MultiAcquirerCheckout\Support\Pipelines\GatewayPipeline;
use Kaninstein\MultiAcquirerCheckout\Tests\TestCase;

class GatewayPipelineTest extends TestCase
{
    public function test_it_falls_back_until_success(): void
    {
        $pipeline = new GatewayPipeline([
            new FakeGateway('first', 1, false),
            new FakeGateway('second', 2, true),
        ]);

        $request = new PaymentRequest(
            amount: Money::fromCents(1000),
            paymentMethod: PaymentMethod::PIX,
            installments: 1,
            customer: Customer::fromArray(['name' => 'John', 'email' => 'john@example.com']),
        );

        $response = $pipeline->process($request);

        $this->assertSame('second', $response->gatewayName);
        $this->assertTrue($response->isSuccessful());
    }

    public function test_it_prioritizes_preferred_gateway(): void
    {
        $pipeline = new GatewayPipeline([
            new FakeGateway('a', 1, true),
            new FakeGateway('b', 2, true),
        ]);

        $request = new PaymentRequest(
            amount: Money::fromCents(1000),
            paymentMethod: PaymentMethod::PIX,
            installments: 1,
            customer: Customer::fromArray(['name' => 'John', 'email' => 'john@example.com']),
            preferredGateway: 'b',
        );

        $response = $pipeline->process($request);

        $this->assertSame('b', $response->gatewayName);
    }
}

class FakeGateway implements GatewayInterface
{
    public function __construct(
        private readonly string $name,
        private readonly int $priority,
        private readonly bool $success,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function supports(string $paymentMethod): bool
    {
        return true;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function process(PaymentRequest $request): PaymentResponse
    {
        return $this->success
            ? PaymentResponse::success($this->name, ['id' => 'tx_123'])
            : PaymentResponse::failed($this->name, 'nope');
    }

    public function getStatus(string $transactionId): PaymentResponse
    {
        return PaymentResponse::failed($this->name, 'not implemented');
    }

    public function refund(string $transactionId, ?int $amountCents = null): PaymentResponse
    {
        return PaymentResponse::failed($this->name, 'not implemented');
    }
}

