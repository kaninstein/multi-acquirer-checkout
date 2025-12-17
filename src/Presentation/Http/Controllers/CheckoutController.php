<?php

namespace Kaninstein\MultiAcquirerCheckout\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kaninstein\MultiAcquirerCheckout\Application\DTOs\PaymentRequest;
use Kaninstein\MultiAcquirerCheckout\Application\Services\CheckoutService;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\CardData;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Customer;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\Money;
use Kaninstein\MultiAcquirerCheckout\Domain\Payment\ValueObjects\PaymentMethod;

class CheckoutController
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'payment_method' => ['required', 'string', 'in:card,pix,boleto'],
            'installments' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'min:1'],
            'customer.email' => ['required', 'email'],
            'customer.document' => ['sometimes', 'nullable', 'string'],
            'customer.phone' => ['sometimes', 'nullable', 'string'],
            'card' => ['sometimes', 'nullable', 'array'],
            'card.number' => ['sometimes', 'string'],
            'card.holder_name' => ['sometimes', 'string'],
            'card.exp_month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'card.exp_year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'card.cvv' => ['sometimes', 'string'],
            'metadata' => ['sometimes', 'array'],
            'gateway' => ['sometimes', 'string'],
            'platform_fee_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'merchant_absorbs_financing' => ['sometimes', 'boolean'],
            'fee_responsibility' => ['sometimes', 'string', 'in:buyer,merchant'],
        ]);

        $amount = Money::fromCents(
            (int) $validated['amount_cents'],
            (string) ($validated['currency'] ?? 'BRL')
        );

        $customer = Customer::fromArray($validated['customer']);

        $card = isset($validated['card']) && is_array($validated['card'])
            ? CardData::fromArray($validated['card'])
            : null;

        $paymentMethod = PaymentMethod::from((string) $validated['payment_method']);

        $result = $this->checkoutService->process(new PaymentRequest(
            amount: $amount,
            paymentMethod: $paymentMethod,
            installments: (int) ($validated['installments'] ?? 1),
            customer: $customer,
            cardData: $card,
            metadata: (array) ($validated['metadata'] ?? []),
            preferredGateway: (string) ($validated['gateway'] ?? ''),
            merchantAbsorbsFinancing: (bool) ($validated['merchant_absorbs_financing'] ?? false),
            feeResponsibility: (string) ($validated['fee_responsibility'] ?? 'buyer'),
            platformFeeRate: array_key_exists('platform_fee_rate', $validated) ? (float) $validated['platform_fee_rate'] : null,
        ));

        return response()->json([
            'success' => $result->success,
            'payment' => $result->payment->toArray(),
            'fees' => $result->fees->toArray(),
            'gateway' => $result->gatewayResponse->toArray(),
        ]);
    }
}
