<?php

namespace Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Eloquent;

use Kaninstein\MultiAquirerCheckout\Infrastructure\Persistence\Models\FeeConfigModel;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\FeeConfigRepositoryInterface;

class EloquentFeeConfigRepository implements FeeConfigRepositoryInterface
{
    public function __construct(
        private readonly FeeConfigModel $model,
    ) {}

    public function getFeeFor(string $gatewayName, string $paymentMethod, int $installments): ?array
    {
        /** @var FeeConfigModel|null $row */
        $row = $this->model->newQuery()
            ->where('gateway_name', $gatewayName)
            ->where('payment_method', $paymentMethod)
            ->where('installments', $installments)
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'percentage' => (float) $row->percentage,
            'fixed_cents' => (int) $row->fixed_fee_cents,
        ];
    }
}

