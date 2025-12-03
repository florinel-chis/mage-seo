<?php

namespace App\Providers;

use App\Services\Magento\Client;
use Illuminate\Support\ServiceProvider;

class MagentoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['services.magento'];

            return new Client(
                baseUrl: $config['base_url'],
                token: $config['token'],
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
