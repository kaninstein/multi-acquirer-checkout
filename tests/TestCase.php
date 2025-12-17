<?php

namespace Kaninstein\MultiAcquirerCheckout\Tests;

use Monolog\Handler\NullHandler;
use Orchestra\Testbench\TestCase as Orchestra;
use Kaninstein\MultiAcquirerCheckout\MultiAcquirerCheckoutServiceProvider;

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
        $app['config']->set('multi-acquirer.routes.enabled', true);

        $app['config']->set('multi-acquirer.logging.channel', 'null');
        $app['config']->set('logging.default', 'null');
        $app['config']->set('logging.channels.null', [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ]);
    }
}
