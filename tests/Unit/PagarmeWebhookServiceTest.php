<?php

namespace Kaninstein\MultiAcquirerCheckout\Tests\Unit;

use Kaninstein\MultiAcquirerCheckout\Application\Services\PagarmeWebhookService;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities\Payment;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\PaymentMethod;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\InMemory\InMemoryPaymentRepository;
use Kaninstein\MultiAcquirerCheckout\Tests\TestCase;

class PagarmeWebhookServiceTest extends TestCase
{
    public function test_charge_paid_marks_payment_as_paid(): void
    {
        $repo = new InMemoryPaymentRepository();
        $service = new PagarmeWebhookService($repo, app('events'));

        $payment = Payment::create(
            amount: Money::fromCents(1000, 'BRL'),
            method: PaymentMethod::from('pix'),
            customer: Customer::fromArray([
                'name' => 'Test',
                'email' => 'test@example.com',
            ]),
            metadata: [],
        );
        $payment->authorize('ch_123');
        $repo->save($payment);

        $result = $service->handle([
            'type' => 'charge.paid',
            'data' => [
                'id' => 'ch_123',
            ],
        ]);

        $this->assertSame(['status' => 'success'], $result);

        $reloaded = $repo->findByGatewayTransactionId('ch_123');
        $this->assertNotNull($reloaded);
        $this->assertSame('paid', $reloaded->status->value);
    }
}

