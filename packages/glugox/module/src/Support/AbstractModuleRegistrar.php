<?php

namespace Glugox\Module\Support;

use Glugox\Module\Contracts\ModuleRegistrar;
use Glugox\Module\Contracts\ModuleRegistry;

abstract class AbstractModuleRegistrar implements ModuleRegistrar
{
    public function register(ModuleRegistry $registry): void
    {
        foreach ($this->serviceProviders() as $serviceProvider) {
            $registry->registerServiceProvider($serviceProvider);
        }

        foreach ($this->routeDefinitions() as $definition) {
            $registry->mergeRoutes($definition['group'], $definition['file']);
        }
    }

    /**
     * @return array<int, string>
     */
    abstract protected function serviceProviders(): array;

    /**
     * @return array<int, array{group: array<string, mixed>, file: string}>
     */
    abstract protected function routeDefinitions(): array;
}
