<?php

namespace Kaninstein\MultiAcquirerCheckout\Application\DTOs;

final readonly class FeeCalculationResult
{
    public function __construct(
        public int $productPriceCents,
        public int $installments,
        public float $platformFeeRateSnapshot,
        public int $platformFeeAmountCents,
        public float $gatewayPercentageSnapshot,
        public int $gatewayFixedFeeCents,
        public int $gatewayFeeCents,
        public int $financingCostCents,
        public bool $merchantAbsorbsFinancing,
        public string $feeResponsibility,
        public int $amountPaidByCustomerCents,
        public int $installmentValueCents,
        public int $merchantNetAmountCents,
        public string $gatewayName,
        public string $paymentMethod,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_price_cents' => $this->productPriceCents,
            'installments' => $this->installments,
            'platform_fee_rate_snapshot' => $this->platformFeeRateSnapshot,
            'platform_fee_amount_cents' => $this->platformFeeAmountCents,
            'gateway_name' => $this->gatewayName,
            'payment_method' => $this->paymentMethod,
            'gateway_percentage_snapshot' => $this->gatewayPercentageSnapshot,
            'gateway_fixed_fee_cents' => $this->gatewayFixedFeeCents,
            'gateway_fee_cents' => $this->gatewayFeeCents,
            'financing_cost_cents' => $this->financingCostCents,
            'merchant_absorbs_financing' => $this->merchantAbsorbsFinancing,
            'fee_responsibility' => $this->feeResponsibility,
            'amount_paid_by_customer_cents' => $this->amountPaidByCustomerCents,
            'installment_value_cents' => $this->installmentValueCents,
            'merchant_net_amount_cents' => $this->merchantNetAmountCents,
        ];
    }
}

