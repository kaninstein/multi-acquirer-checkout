<?php

namespace Kaninstein\MultiAcquirerCheckout\Tests\Feature;

use Kaninstein\MultiAcquirerCheckout\Tests\TestCase;

final class FeeControllerTest extends TestCase
{
    public function test_calculates_fees_with_defaults(): void
    {
        $this->app['config']->set('multi-acquirer.routes.enabled', true);
        $this->app['config']->set('multi-acquirer.fees.platform.default_rate', 0.06);

        $response = $this->postJson('/api/multi-acquirer/fees', [
            'amount_cents' => 10000,
            'payment_method' => 'card',
            'installments' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['fees' => ['platform_fee_amount_cents', 'gateway_fee_cents']]);
        $response->assertJsonPath('fees.product_price_cents', 10000);
        $response->assertJsonPath('fees.installments', 1);
        $response->assertJsonPath('fees.platform_fee_rate_snapshot', 0.06);
    }

    public function test_calculates_fees_with_override_platform_fee_rate(): void
    {
        $this->app['config']->set('multi-acquirer.routes.enabled', true);

        $response = $this->postJson('/api/multi-acquirer/fees', [
            'amount_cents' => 10000,
            'payment_method' => 'pix',
            'platform_fee_rate' => 0.1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('fees.platform_fee_rate_snapshot', 0.1);
        $response->assertJsonPath('fees.platform_fee_amount_cents', 1000);
    }
}

