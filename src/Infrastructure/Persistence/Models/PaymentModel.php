<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'amount_cents',
        'currency',
        'status',
        'payment_method',
        'customer_data',
        'gateway_transaction_id',
        'failure_reason',
        'metadata',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'customer_data' => 'array',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return (string) config('multi-acquirer.database.tables.payments', parent::getTable());
    }
}

