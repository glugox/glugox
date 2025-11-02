<?php

namespace Glugox\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\FreshModulesCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        // TODO: Wire ModuleLoader discovery and module service provider registration.
    }
}
