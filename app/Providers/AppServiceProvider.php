<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            \App\Services\WorkflowRunnerFactory::class,
            fn($app) => new \App\Services\WorkflowRunnerFactory(
                $app->make(\App\Services\ProjectService::class),
            ),
        );
    }

    public function boot(): void
    {
        //
    }
}
