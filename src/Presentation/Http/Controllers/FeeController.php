<?php

namespace Kaninstein\MultiAcquirerCheckout\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kaninstein\MultiAcquirerCheckout\Domain\Fee\Services\FeeCalculator;

class FeeController
{
    public function __construct(private readonly FeeCalculator $fees)
    {
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:card,pix,boleto'],
            'installments' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'gateway' => ['sometimes', 'string'],
            'platform_fee_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'merchant_absorbs_financing' => ['sometimes', 'boolean'],
            'fee_responsibility' => ['sometimes', 'string', 'in:buyer,merchant'],
        ]);

        $platformRate = array_key_exists('platform_fee_rate', $validated)
            ? (float) $validated['platform_fee_rate']
            : (float) config('multi-acquirer.fees.platform.default_rate', 0.06);

        $result = $this->fees->calculate(
            productPriceCents: (int) $validated['amount_cents'],
            installments: (int) ($validated['installments'] ?? 1),
            platformFeeRate: $platformRate,
            merchantAbsorbsFinancing: (bool) ($validated['merchant_absorbs_financing'] ?? false),
            paymentMethod: (string) $validated['payment_method'],
            gatewayName: (string) ($validated['gateway'] ?? 'pagarme'),
            feeResponsibility: (string) ($validated['fee_responsibility'] ?? 'buyer'),
        );

        return response()->json([
            'fees' => $result->toArray(),
        ]);
    }
}

