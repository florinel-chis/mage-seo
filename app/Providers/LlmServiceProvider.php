<?php

namespace App\Providers;

use App\Services\LLM\WriterAuditor;
use Illuminate\Support\ServiceProvider;

class LlmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WriterAuditor::class, function ($app) {
            $geminiConfig = $app['config']['services.gemini'];

            return new WriterAuditor(
                apiKey: $geminiConfig['api_key'],
                model: $geminiConfig['model'],
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
