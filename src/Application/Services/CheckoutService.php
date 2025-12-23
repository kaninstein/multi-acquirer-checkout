<?php

namespace Kaninstein\MultiAcquirerCheckout\Application\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\CheckoutResult;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Domain\Fee\Contracts\FeeCalculatorInterface;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\Entities\Payment;
use Kaninstein\MultiAcquirerCheckout\Domain\Validation\ValidationContexts;
use Kaninstein\MultiAcquirerCheckout\Domain\Validation\ValidationManager;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts\PaymentRepositoryInterface;
use Kaninstein\MultiAcquirerCheckout\Support\Hooks\HookManager;
use Kaninstein\MultiAcquirerCheckout\Support\Hooks\HookPoints;
use Kaninstein\MultiAcquirerCheckout\Support\Pipelines\GatewayPipeline;

class CheckoutService
{
    public function __construct(
        private readonly GatewayPipeline $pipeline,
        private readonly FeeCalculatorInterface $feeCalculator,
        private readonly PaymentRepositoryInterface $payments,
        private readonly Dispatcher $events,
        private readonly HookManager $hooks,
        private readonly ValidationManager $validator,
    ) {}

    public function process(PaymentRequest $request): CheckoutResult
    {
        // Hook: Before payment
        $request = $this->hooks->execute(HookPoints::BEFORE_PAYMENT, $request);

        // Validation: Payment request
        if (config('multi-acquirer.validation.enabled', true)) {
            $validationResult = $this->validator->validate(ValidationContexts::PAYMENT_REQUEST, $request);

            if (!$validationResult->isValid) {
                throw new \InvalidArgumentException(
                    'Payment validation failed: ' . implode(', ', $validationResult->getAllErrors())
                );
            }
        }

        // Hook: Before validation
        $request = $this->hooks->execute(HookPoints::BEFORE_VALIDATION, $request);

        // Hook: After validation
        $request = $this->hooks->execute(HookPoints::AFTER_VALIDATION, $request);

        $payment = Payment::create(
            amount: $request->amount,
            method: $request->paymentMethod,
            customer: $request->customer,
            metadata: $request->metadata,
        );

        $platformRate = $request->platformFeeRate ?? (float) config('multi-acquirer.fees.platform.default_rate', 0.06);

        // Hook: Before fee calculation
        $feeContext = ['request' => $request, 'platformRate' => $platformRate];
        $feeContext = $this->hooks->execute(HookPoints::BEFORE_FEE_CALCULATION, $feeContext);

        // Fee calculation is based on the product/base price.
        $fees = $this->feeCalculator->calculate(
            productPriceCents: $request->amount->amountInCents,
            installments: $request->installments,
            platformFeeRate: $feeContext['platformRate'] ?? $platformRate,
            merchantAbsorbsFinancing: $request->merchantAbsorbsFinancing,
            paymentMethod: $request->paymentMethod->value,
            gatewayName: $request->preferredGateway !== '' ? $request->preferredGateway : 'pagarme',
            feeResponsibility: $request->feeResponsibility,
        );

        // Hook: After fee calculation
        $fees = $this->hooks->execute(HookPoints::AFTER_FEE_CALCULATION, $fees);

        // Hook: Before gateway processing
        $request = $this->hooks->execute(HookPoints::BEFORE_GATEWAY, $request);

        $gatewayResponse = $this->pipeline->process($request);

        // Hook: After gateway processing
        $gatewayResponse = $this->hooks->execute(HookPoints::AFTER_GATEWAY, $gatewayResponse);

        if ($gatewayResponse->isSuccessful()) {
            if ($gatewayResponse->id) {
                $payment->authorize($gatewayResponse->id);
            }

            if ($gatewayResponse->status === 'paid') {
                $payment->markPaid();
            }

            // Hook: Payment success
            $successContext = ['payment' => $payment, 'response' => $gatewayResponse];
            $this->hooks->execute(HookPoints::ON_PAYMENT_SUCCESS, $successContext);
        } else {
            $payment->fail($gatewayResponse->errorMessage ?? 'Unknown error');

            // Hook: Payment failure
            $failureContext = ['payment' => $payment, 'response' => $gatewayResponse];
            $this->hooks->execute(HookPoints::ON_PAYMENT_FAILURE, $failureContext);
        }

        $this->payments->save($payment);

        if (config('multi-acquirer.events.dispatch_domain_events', true)) {
            foreach ($payment->releaseEvents() as $event) {
                $this->events->dispatch($event);
            }
        }

        // Hook: After payment
        $result = new CheckoutResult(
            success: $gatewayResponse->isSuccessful(),
            payment: $payment,
            fees: $fees,
            gatewayResponse: $gatewayResponse,
        );

        $result = $this->hooks->execute(HookPoints::AFTER_PAYMENT, $result);

        return $result;
    }
}
