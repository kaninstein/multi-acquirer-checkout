<?php

namespace Kaninstein\MultiAcquirerCheckout\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'amount_cents',
        'currency',
        'customer_data',
        'metadata',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'customer_data' => 'array',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return (string) config('multi-acquirer.database.tables.orders', parent::getTable());
    }
}

