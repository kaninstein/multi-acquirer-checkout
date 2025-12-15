<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class FeeConfigModel extends Model
{
    protected $fillable = [
        'gateway_name',
        'payment_method',
        'installments',
        'percentage',
        'fixed_fee_cents',
        'metadata',
    ];

    protected $casts = [
        'installments' => 'integer',
        'percentage' => 'decimal:6',
        'fixed_fee_cents' => 'integer',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return (string) config('multi-acquirer.database.tables.fee_configurations', parent::getTable());
    }
}

