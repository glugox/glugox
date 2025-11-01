<?php

namespace Glugox\Module\Contracts;

interface ModuleRegistry
{
    /**
     * Register a module service provider with the host application.
     */
    public function registerServiceProvider(string $providerClass): void;

    /**
     * Merge a module route file into the host application.
     *
     * @param array<string, mixed> $groupAttributes
     */
    public function mergeRoutes(array $groupAttributes, string $routesFile): void;
}
