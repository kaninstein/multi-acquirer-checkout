<?php

namespace Kaninstein\MultiAcquirerCheckout\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\CheckoutResult;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Domain\Fee\Services\FeeCalculator;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities\Payment;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts\PaymentRepositoryInterface;
use Kaninstein\MultiAcquirerCheckout\Support\Pipelines\GatewayPipeline;

class CheckoutService
{
    public function __construct(
        private readonly GatewayPipeline $pipeline,
        private readonly FeeCalculator $feeCalculator,
        private readonly PaymentRepositoryInterface $payments,
        private readonly Dispatcher $events,
    ) {}

    public function process(PaymentRequest $request): CheckoutResult
    {
        $payment = Payment::create(
            amount: $request->amount,
            method: $request->paymentMethod,
            customer: $request->customer,
            metadata: $request->metadata,
        );

        $platformRate = $request->platformFeeRate ?? (float) config('multi-acquirer.fees.platform.default_rate', 0.06);

        // Fee calculation is based on the product/base price.
        $fees = $this->feeCalculator->calculate(
            productPriceCents: $request->amount->amountInCents,
            installments: $request->installments,
            platformFeeRate: $platformRate,
            merchantAbsorbsFinancing: $request->merchantAbsorbsFinancing,
            paymentMethod: $request->paymentMethod->value,
            gatewayName: $request->preferredGateway !== '' ? $request->preferredGateway : 'pagarme',
            feeResponsibility: $request->feeResponsibility,
        );

        $gatewayResponse = $this->pipeline->process($request);

        if ($gatewayResponse->isSuccessful()) {
            if ($gatewayResponse->id) {
                $payment->authorize($gatewayResponse->id);
            }

            if ($gatewayResponse->status === 'paid') {
                $payment->markPaid();
            }
        } else {
            $payment->fail($gatewayResponse->errorMessage ?? 'Unknown error');
        }

        $this->payments->save($payment);

        if (config('multi-acquirer.events.dispatch_domain_events', true)) {
            foreach ($payment->releaseEvents() as $event) {
                $this->events->dispatch($event);
            }
        }

        return new CheckoutResult(
            success: $gatewayResponse->isSuccessful(),
            payment: $payment,
            fees: $fees,
            gatewayResponse: $gatewayResponse,
        );
    }
}
