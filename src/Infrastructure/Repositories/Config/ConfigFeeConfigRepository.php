<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Config;

use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\FeeConfigRepositoryInterface;

class ConfigFeeConfigRepository implements FeeConfigRepositoryInterface
{
    public function getFeeFor(string $gatewayName, string $paymentMethod, int $installments): ?array
    {
        $gatewayFees = config("multi-acquirer.fees.gateways.{$gatewayName}", []);
        if (! is_array($gatewayFees)) {
            return null;
        }

        if ($paymentMethod === 'card') {
            $row = $gatewayFees['card'][(string) $installments] ?? null;
            if (! is_array($row)) {
                return null;
            }

            return [
                'percentage' => (float) ($row['percentage'] ?? 0),
                'fixed_cents' => (int) ($row['fixed_cents'] ?? 0),
            ];
        }

        if ($paymentMethod === 'pix' || $paymentMethod === 'boleto') {
            $row = $gatewayFees[$paymentMethod] ?? null;
            if (! is_array($row)) {
                return null;
            }

            return [
                'percentage' => (float) ($row['percentage'] ?? 0),
                'fixed_cents' => (int) ($row['fixed_cents'] ?? 0),
            ];
        }

        return null;
    }
}

