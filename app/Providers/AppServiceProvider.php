<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Scale Service
        $this->app->bind(
            \App\Services\Scale\ScaleServiceInterface::class,
            \App\Services\Scale\MockScaleService::class
        );

        // Bind Label Printer Service
        $this->app->bind(
            \App\Services\Label\LabelPrinterInterface::class,
            \App\Services\Label\MockLabelPrinterService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
