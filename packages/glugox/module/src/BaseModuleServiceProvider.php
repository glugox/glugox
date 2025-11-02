<?php

namespace Glugox\Module;

use Illuminate\Support\ServiceProvider;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRoutes();
    }

    abstract public function registerRoutes(): void;
}
