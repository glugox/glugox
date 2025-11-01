<?php

namespace Glugox\Core\Orchestrator;

class ModuleManifest
{
    /**
     * @param array<int, array{file: string, group: array<string, mixed>}> $routes
     * @param array<string, string> $psr4
     */
    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly string $registrar,
        private readonly array $serviceProviders,
        private readonly array $routes,
        private readonly array $psr4
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function registrar(): string
    {
        return $this->registrar;
    }

    /**
     * @return array<int, string>
     */
    public function serviceProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * @return array<int, array{file: string, group: array<string, mixed>}> 
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @return array<string, string>
     */
    public function psr4(): array
    {
        return $this->psr4;
    }
}
