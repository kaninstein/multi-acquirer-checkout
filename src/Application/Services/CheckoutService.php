<?php

namespace Kaninstein\MultiAquirerCheckout\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\CheckoutResult;
use Kaninstein\MultiAquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAquirerCheckout\Domain\Fee\Services\FeeCalculator;
use Kaninstein\MultiAquirerCheckout\Domain\Payment\Entities\Payment;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\PaymentRepositoryInterface;
use Kaninstein\MultiAquirerCheckout\Support\Pipelines\GatewayPipeline;

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

        $platformRate = (float) config('multi-acquirer.fees.platform.default_rate', 0.06);

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
