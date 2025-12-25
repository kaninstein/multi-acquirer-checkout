<?php

namespace Kaninstein\MultiAcquirerCheckout\Domain\Fee\Services;

use Kaninstein\MultiAcquirerCheckout\Application\DTOs\FeeCalculationResult;
use Kaninstein\MultiAcquirerCheckout\Domain\Fee\Contracts\FeeCalculatorInterface;
use Kaninstein\MultiAcquirerCheckout\Domain\Fee\ValueObjects\FeeBreakdown;
use Kaninstein\MultiAcquirerCheckout\Infrastructure\Repositories\Contracts\FeeConfigRepositoryInterface;

class FeeCalculator implements FeeCalculatorInterface
{
    public function __construct(
        private readonly FeeConfigRepositoryInterface $feeConfigs,
    ) {}

    public function calculate(
        int $productPriceCents,
        int $installments,
        float $platformFeeRate,
        bool $merchantAbsorbsFinancing,
        string $paymentMethod = 'card',
        string $gatewayName = 'pagarme',
        string $feeResponsibility = 'buyer',
    ): FeeBreakdown {
        $installments = max(1, min(12, $installments));

        $platformFeeCents = $this->calculatePlatformFee($productPriceCents, $platformFeeRate);

        $gatewayConfig = $this->getGatewayConfig($gatewayName, $paymentMethod, $installments);

        if ($merchantAbsorbsFinancing) {
            $amountPaidByCustomer = $productPriceCents;

            $gatewayFee = $this->calculateGatewayFee(
                $productPriceCents,
                $gatewayConfig['percentage'],
                $gatewayConfig['fixed_cents']
            );

            $baseConfig = $this->getGatewayConfig($gatewayName, $paymentMethod, 1);
            $baseGatewayFee = $this->calculateGatewayFee(
                $productPriceCents,
                $baseConfig['percentage'],
                $baseConfig['fixed_cents']
            );

            $financingCost = $gatewayFee - $baseGatewayFee;
        } else {
            $grossAmount = $this->grossUp(
                $productPriceCents,
                $gatewayConfig['percentage'],
                $gatewayConfig['fixed_cents'],
                $feeResponsibility
            );

            $amountPaidByCustomer = $grossAmount;

            $gatewayFee = $this->calculateGatewayFee(
                $grossAmount,
                $gatewayConfig['percentage'],
                $gatewayConfig['fixed_cents']
            );

            $baseConfig = $this->getGatewayConfig($gatewayName, $paymentMethod, 1);
            $baseGrossAmount = $this->grossUp(
                $productPriceCents,
                $baseConfig['percentage'],
                $baseConfig['fixed_cents'],
                $feeResponsibility
            );
            $baseGatewayFee = $this->calculateGatewayFee(
                $baseGrossAmount,
                $baseConfig['percentage'],
                $baseConfig['fixed_cents']
            );

            $financingCost = $gatewayFee - $baseGatewayFee;
        }

        $merchantNet = $amountPaidByCustomer - $platformFeeCents - $gatewayFee;

        $installmentValue = (int) ceil($amountPaidByCustomer / $installments);

        return new FeeBreakdown(
            productPriceCents: $productPriceCents,
            installments: $installments,
            platformFeeRateSnapshot: $platformFeeRate,
            platformFeeAmountCents: $platformFeeCents,
            gatewayPercentageSnapshot: (float) $gatewayConfig['percentage'],
            gatewayFixedFeeCents: (int) $gatewayConfig['fixed_cents'],
            gatewayFeeCents: $gatewayFee,
            financingCostCents: $financingCost,
            merchantAbsorbsFinancing: $merchantAbsorbsFinancing,
            feeResponsibility: $feeResponsibility,
            amountPaidByCustomerCents: $amountPaidByCustomer,
            installmentValueCents: $installmentValue,
            merchantNetAmountCents: $merchantNet,
            gatewayName: $gatewayName,
            paymentMethod: $paymentMethod,
        );
    }

    private function calculatePlatformFee(int $amount, float $rate): int
    {
        return (int) bcmul((string) $amount, (string) $rate, 0);
    }

    private function calculateGatewayFee(int $amount, float $percentage, int $fixedCents): int
    {
        $percentageFee = (int) bcmul((string) $amount, (string) $percentage, 0);

        return $percentageFee + $fixedCents;
    }

    private function grossUp(int $baseAmount, float $percentage, int $fixedCents, string $feeResponsibility): int
    {
        if ($feeResponsibility === 'merchant') {
            return $baseAmount;
        }

        $numerator = bcadd((string) $baseAmount, (string) $fixedCents, 0);
        $denominator = bcsub('1', (string) $percentage, 10);

        return (int) bcdiv($numerator, $denominator, 0);
    }

    /**
     * @return array{percentage: float, fixed_cents: int}
     */
    private function getGatewayConfig(string $gatewayName, string $paymentMethod, int $installments): array
    {
        $config = $this->feeConfigs->getFeeFor($gatewayName, $paymentMethod, $installments);
        if ($config !== null) {
            return $config;
        }

        if ($paymentMethod === 'pix') {
            return ['percentage' => 0.0119, 'fixed_cents' => 0];
        }

        if ($paymentMethod === 'boleto') {
            return ['percentage' => 0.0, 'fixed_cents' => 349];
        }

        // card
        return ['percentage' => 0.0559, 'fixed_cents' => 99];
    }

    public function getGatewayFeeRate(
        string $gatewayName,
        string $paymentMethod,
        int $installments
    ): float {
        $config = $this->getGatewayConfig($gatewayName, $paymentMethod, $installments);
        return (float) $config['percentage'];
    }

    public function calculateFinancingFee(
        int $amountCents,
        int $installments,
        float $monthlyRate
    ): int {
        if ($installments <= 1) {
            return 0;
        }

        // Simple interest calculation for financing
        $months = $installments - 1;
        return (int) bcmul((string) $amountCents, (string) ($monthlyRate * $months), 0);
    }
}
