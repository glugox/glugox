<?php

namespace Glugox\Core\Orchestrator;

use Glugox\Module\Contracts\ModuleRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;

class LaravelModuleRegistry implements ModuleRegistry
{
    public function __construct(
        private readonly Application $app,
        private readonly Router $router
    ) {
    }

    public function registerServiceProvider(string $providerClass): void
    {
        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    public function mergeRoutes(array $groupAttributes, string $routesFile): void
    {
        if (!is_file($routesFile)) {
            return;
        }

        $this->router->group($groupAttributes, function () use ($routesFile) {
            require $routesFile;
        });
    }
}
