<?php
    namespace FirstIraqiBank\FIBPaymentSDK;

    use App\Services\Contracts\FIBPaymentIntegrationServiceInterface;
    use App\Services\Contracts\FIBPaymentRepositoryInterface;
    use App\Services\FIBAuthIntegrationService;
    use App\Services\FIBPaymentIntegrationService;
    use App\Services\FIBPaymentRepositoryService;
    use Illuminate\Support\ServiceProvider;

    class FIBPaymentServiceProvider extends ServiceProvider
    {
        public function register(): void
        {
            $this->app->bind(FIBPaymentIntegrationServiceInterface::class, FIBPaymentIntegrationService::class);
            $this->app->bind(FIBPaymentRepositoryInterface::class, FIBPaymentRepositoryService::class);


            $this->app->singleton(FIBAuthIntegrationService::class, function () {
                return new FIBAuthIntegrationService();
            });

            $this->app->singleton(FIBPaymentIntegrationService::class, function ($app) {
                return new FIBPaymentIntegrationService(
                    $app->make(FIBPaymentRepositoryService::class),
                    $app->make(FIBAuthIntegrationService::class)
                );
            });
        }

        /**
         * Bootstrap any application services.
         *
         * @return void
         */
        public function boot(): void
        {
            $this->loadRoutesFrom(__DIR__ . "/routes/api.php");
            $this->loadMigrationsFrom(__DIR__ . "/database/migrations");
            $this->mergeConfigFrom(__DIR__ . '/config/fib.php', 'fib');
            $this->publishes([__DIR__ . '/config/fib.php' => config_path('fib.php')]);
        }
    }
