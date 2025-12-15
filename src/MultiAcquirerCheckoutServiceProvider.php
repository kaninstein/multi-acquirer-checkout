<?php

namespace Kaninstein\MultiAquirerCheckout;

use Illuminate\Support\ServiceProvider;
use Kaninstein\MultiAquirerCheckout\Application\Services\CheckoutService;
use Kaninstein\MultiAquirerCheckout\Domain\Fee\Services\FeeCalculator;
use Kaninstein\MultiAquirerCheckout\Domain\Gateway\Contracts\GatewayInterface;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\Appmax\AppmaxGateway;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\MercadoPago\MercadoPagoGateway;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\Pagarme\PagarmeGateway;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Gateways\Stripe\StripeGateway;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\FeeConfigRepositoryInterface;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\OrderRepositoryInterface;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Contracts\PaymentRepositoryInterface;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Eloquent\EloquentFeeConfigRepository;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Eloquent\EloquentOrderRepository;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Eloquent\EloquentPaymentRepository;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\Config\ConfigFeeConfigRepository;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\InMemory\InMemoryFeeConfigRepository;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\InMemory\InMemoryOrderRepository;
use Kaninstein\MultiAquirerCheckout\Infrastructure\Repositories\InMemory\InMemoryPaymentRepository;
use Kaninstein\MultiAquirerCheckout\Support\Pipelines\GatewayPipeline;

class MultiAcquirerCheckoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/multi-acquirer.php',
            'multi-acquirer'
        );

        // Register gateways
        $this->registerGateways();

        // Register repositories
        $this->registerRepositories();

        // Register application services
        $this->registerServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/multi-acquirer.php' => config_path('multi-acquirer.php'),
        ], 'multi-acquirer-config');

        // Load routes (optional)
        if (config('multi-acquirer.routes.enabled', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        // Publish migrations (if enabled)
        if ($this->app->runningInConsole() && config('multi-acquirer.database.use_package_migrations')) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'multi-acquirer-migrations');
        }

        // Load migrations (if package migrations are enabled)
        if (config('multi-acquirer.database.use_package_migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register payment gateways
     */
    protected function registerGateways(): void
    {
        // Register each gateway as singleton
        $this->app->singleton(PagarmeGateway::class, function ($app) {
            $config = config('multi-acquirer.gateways.pagarme', []);
            return new PagarmeGateway($config);
        });

        $this->app->singleton(AppmaxGateway::class, function ($app) {
            $config = config('multi-acquirer.gateways.appmax', []);
            return new AppmaxGateway($config);
        });

        $this->app->singleton(StripeGateway::class, function ($app) {
            $config = config('multi-acquirer.gateways.stripe', []);
            return new StripeGateway($config);
        });

        $this->app->singleton(MercadoPagoGateway::class, function ($app) {
            $config = config('multi-acquirer.gateways.mercadopago', []);
            return new MercadoPagoGateway($config);
        });

        // Bind default gateway interface to Pagarme (highest priority)
        $this->app->bind(GatewayInterface::class, PagarmeGateway::class);

        // Register Gateway Pipeline with all gateways
        $this->app->singleton(GatewayPipeline::class, function ($app) {
            $gateways = [
                $app->make(PagarmeGateway::class),
                $app->make(AppmaxGateway::class),
                $app->make(StripeGateway::class),
                $app->make(MercadoPagoGateway::class),
            ];

            return new GatewayPipeline($gateways);
        });
    }

    /**
     * Register repositories
     */
    protected function registerRepositories(): void
    {
        if (config('multi-acquirer.database.use_package_migrations', false)) {
            $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
            $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
            $this->app->bind(FeeConfigRepositoryInterface::class, EloquentFeeConfigRepository::class);
        } else {
            $this->app->singleton(PaymentRepositoryInterface::class, InMemoryPaymentRepository::class);
            $this->app->singleton(OrderRepositoryInterface::class, InMemoryOrderRepository::class);
            $this->app->singleton(FeeConfigRepositoryInterface::class, ConfigFeeConfigRepository::class);
        }
    }

    /**
     * Register application services
     */
    protected function registerServices(): void
    {
        $this->app->singleton(CheckoutService::class);
        $this->app->singleton(FeeCalculator::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            CheckoutService::class,
            FeeCalculator::class,
            GatewayPipeline::class,
            PaymentRepositoryInterface::class,
            OrderRepositoryInterface::class,
            FeeConfigRepositoryInterface::class,
        ];
    }
}
