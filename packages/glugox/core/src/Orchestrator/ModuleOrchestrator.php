<?php

namespace Glugox\Core\Orchestrator;

use Glugox\Core\Support\Psr4Autoloader;
use Glugox\Module\Contracts\ModuleRegistrar;
use Glugox\Module\Contracts\ModuleRegistry;
use Illuminate\Contracts\Foundation\Application;
use Throwable;

class ModuleOrchestrator
{
    public function __construct(
        private readonly ModuleDiscovery $discovery,
        private readonly Psr4Autoloader $autoloader,
        private readonly Application $app,
        private readonly ModuleRegistry $registry
    ) {
        $this->autoloader->register();
    }

    public function boot(): void
    {
        foreach ($this->discovery->discover() as $manifest) {
            $this->registerAutoloading($manifest);
            $this->registerModule($manifest);
        }
    }

    private function registerAutoloading(ModuleManifest $manifest): void
    {
        foreach ($manifest->psr4() as $namespace => $relativePath) {
            $baseDir = $manifest->path() . DIRECTORY_SEPARATOR . trim($relativePath, DIRECTORY_SEPARATOR . '/');
            $this->autoloader->addNamespace($namespace, $baseDir);
        }
    }

    private function registerModule(ModuleManifest $manifest): void
    {
        $registrarClass = $manifest->registrar();

        try {
            $registrar = $this->app->make($registrarClass);
        } catch (Throwable $e) {
            return;
        }

        if ($registrar instanceof ModuleRegistrar) {
            $registrar->register($this->registry);
            return;
        }

        foreach ($manifest->serviceProviders() as $provider) {
            $this->registry->registerServiceProvider($provider);
        }

        foreach ($manifest->routes() as $route) {
            $this->registry->mergeRoutes($route['group'], $route['file']);
        }
    }
}
