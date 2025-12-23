<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Fee\Contracts;

use Kaninstein\MultiAcquirerCheckout\Domain\Fee\ValueObjects\FeeBreakdown;

interface FeeCalculatorInterface
{
    /**
     * Calculate fees for a payment
     *
     * @param  int  $productPriceCents Base product price in cents
     * @param  int  $installments Number of installments
     * @param  float  $platformFeeRate Platform fee rate (e.g., 0.06 for 6%)
     * @param  bool  $merchantAbsorbsFinancing Whether merchant absorbs financing costs
     * @param  string  $paymentMethod Payment method (card, pix, boleto)
     * @param  string  $gatewayName Gateway name (pagarme, stripe, etc.)
     * @param  string  $feeResponsibility Who pays fees: merchant, customer, or split
     * @return FeeBreakdown
     */
    public function calculate(
        int $productPriceCents,
        int $installments,
        float $platformFeeRate,
        bool $merchantAbsorbsFinancing,
        string $paymentMethod,
        string $gatewayName,
        string $feeResponsibility = 'merchant'
    ): FeeBreakdown;

    /**
     * Get gateway fee rate for a specific configuration
     *
     * @param  string  $gatewayName
     * @param  string  $paymentMethod
     * @param  int  $installments
     * @return float Fee rate (e.g., 0.0399 for 3.99%)
     */
    public function getGatewayFeeRate(
        string $gatewayName,
        string $paymentMethod,
        int $installments
    ): float;

    /**
     * Calculate financing fee for installments
     *
     * @param  int  $amountCents
     * @param  int  $installments
     * @param  float  $monthlyRate Monthly interest rate
     * @return int Financing fee in cents
     */
    public function calculateFinancingFee(
        int $amountCents,
        int $installments,
        float $monthlyRate
    ): int;
}
