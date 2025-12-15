<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    |
    | Configure available payment gateways and their settings.
    | Each gateway can be enabled/disabled and has a priority order.
    | Lower priority number = higher priority in fallback chain.
    |
    */
    'gateways' => [
        'pagarme' => [
            'enabled' => env('PAGARME_ENABLED', true),
            'priority' => 1,
            'sandbox' => env('PAGARME_SANDBOX', false),
            'timeout' => 30,
            'secret_key' => env('PAGARME_SECRET_KEY'),
            'public_key' => env('PAGARME_PUBLIC_KEY'),
            'account_id' => env('PAGARME_ACCOUNT_ID'),
        ],

        'appmax' => [
            'enabled' => env('APPMAX_ENABLED', false),
            'priority' => 2,
            'sandbox' => env('APPMAX_SANDBOX', false),
            'timeout' => 30,
            'api_key' => env('APPMAX_API_KEY'),
            'sandbox_api_key' => env('APPMAX_SANDBOX_API_KEY'),
        ],

        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
            'priority' => 3,
            'sandbox' => env('STRIPE_SANDBOX', false),
            'timeout' => 30,
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'mercadopago' => [
            'enabled' => env('MERCADOPAGO_ENABLED', false),
            'priority' => 4,
            'sandbox' => env('MERCADOPAGO_SANDBOX', false),
            'timeout' => 30,
            'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
            'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fee Configuration
    |--------------------------------------------------------------------------
    |
    | Platform fee settings and merchant override permissions.
    |
    */
    'fees' => [
        'platform' => [
            'default_rate' => 0.06, // 6% default platform fee
        ],
        'allow_merchant_override' => true,
        'allow_product_override' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods Configuration
    |--------------------------------------------------------------------------
    |
    | Enable/disable payment methods and configure their settings.
    |
    */
    'payment_methods' => [
        'credit_card' => [
            'enabled' => true,
            'max_installments' => 12,
            'min_installment_amount' => 500, // R$ 5.00 minimum per installment
        ],
        'pix' => [
            'enabled' => true,
            'expiration_seconds' => 3600, // 1 hour
        ],
        'boleto' => [
            'enabled' => true,
            'due_days' => 3,
            'max_days' => 7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database table names and whether to use package migrations.
    |
    */
    'database' => [
        'use_package_migrations' => env('MULTI_ACQUIRER_DATABASE_USE_PACKAGE_MIGRATIONS', false),
        'tables' => [
            'payments' => 'multi_acquirer_payments',
            'orders' => 'multi_acquirer_orders',
            'fee_configurations' => 'multi_acquirer_fee_configurations',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration (optional)
    |--------------------------------------------------------------------------
    |
    | Enable package routes if you want a headless HTTP API for checkout/webhooks.
    |
    */
    'routes' => [
        'enabled' => env('MULTI_ACQUIRER_ROUTES_ENABLED', false),
        'prefix' => env('MULTI_ACQUIRER_ROUTES_PREFIX', 'api/multi-acquirer'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for payment operations.
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => env('PAYMENT_LOG_CHANNEL', 'stack'),
        'sanitize_sensitive_data' => true,
        'log_level' => env('PAYMENT_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook handling and security.
    |
    */
    'webhooks' => [
        'validate_signature' => true,
        'retry_failed' => true,
        'max_retries' => 3,
        'retry_delay_seconds' => 60,
        'timeout_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Default currency and supported currencies.
    |
    */
    'currency' => [
        'default' => 'BRL',
        'supported' => ['BRL', 'USD', 'EUR'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which domain events should be dispatched.
    |
    */
    'events' => [
        'dispatch_domain_events' => true,
        'async' => true, // Queue events for async processing
    ],
];
