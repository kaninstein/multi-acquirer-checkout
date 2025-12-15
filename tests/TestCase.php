<?php

namespace Kaninstein\MultiAquirerCheckout\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Kaninstein\MultiAquirerCheckout\MultiAcquirerCheckoutServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MultiAcquirerCheckoutServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('multi-acquirer.logging.channel', 'null');
        $app['config']->set('logging.channels.null', [
            'driver' => 'null',
        ]);
    }
}

