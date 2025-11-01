<?php

namespace Glugox\Core\Providers;

use Glugox\Core\Orchestrator\LaravelModuleRegistry;
use Glugox\Core\Orchestrator\ModuleDiscovery;
use Glugox\Core\Orchestrator\ModuleOrchestrator;
use Glugox\Core\Support\Psr4Autoloader;
use Glugox\Module\Contracts\ModuleRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class GlugoxCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleDiscovery::class, function (Application $app) {
            $modulesPath = $app['config']['glugox.modules_path'] ?? $this->defaultModulesPath();

            return new ModuleDiscovery($modulesPath);
        });

        $this->app->singleton(Psr4Autoloader::class, fn() => new Psr4Autoloader());

        $this->app->singleton(ModuleRegistry::class, function (Application $app) {
            return new LaravelModuleRegistry($app, $app->make(Router::class));
        });

        $this->app->singleton(ModuleOrchestrator::class, function (Application $app) {
            return new ModuleOrchestrator(
                $app->make(ModuleDiscovery::class),
                $app->make(Psr4Autoloader::class),
                $app,
                $app->make(ModuleRegistry::class)
            );
        });
    }

    public function boot(ModuleOrchestrator $orchestrator): void
    {
        $orchestrator->boot();
    }

    private function defaultModulesPath(): string
    {
        if (function_exists('base_path')) {
            return base_path('modules');
        }

        return getcwd() . DIRECTORY_SEPARATOR . 'modules';
    }
}
