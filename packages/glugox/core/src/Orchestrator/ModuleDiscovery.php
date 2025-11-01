<?php

namespace Glugox\Core\Orchestrator;

use InvalidArgumentException;

class ModuleDiscovery
{
    public function __construct(private readonly string $modulesPath)
    {
    }

    /**
     * @return array<int, ModuleManifest>
     */
    public function discover(): array
    {
        if (!is_dir($this->modulesPath)) {
            return [];
        }

        $manifests = [];
        $directories = glob($this->modulesPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

        foreach ($directories as $directory) {
            $manifestFile = $directory . DIRECTORY_SEPARATOR . 'module.json';
            if (!is_file($manifestFile)) {
                continue;
            }

            $data = json_decode(file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
            $manifests[] = $this->hydrateManifest($directory, $data);
        }

        return $manifests;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrateManifest(string $directory, array $data): ModuleManifest
    {
        foreach (['name', 'registrar', 'service_providers', 'routes', 'psr4'] as $required) {
            if (!array_key_exists($required, $data)) {
                throw new InvalidArgumentException("Module manifest at {$directory} is missing required field [{$required}].");
            }
        }

        $routes = array_map(function (array $route) use ($directory) {
            $file = $directory . DIRECTORY_SEPARATOR . $route['file'];
            $group = $route['group'] ?? [];

            if (!is_array($group)) {
                $group = [];
            }

            return [
                'file' => realpath($file) ?: $file,
                'group' => $group,
            ];
        }, $data['routes']);

        $serviceProviders = array_values(array_filter($data['service_providers'], 'is_string'));

        return new ModuleManifest(
            $data['name'],
            $directory,
            $data['registrar'],
            $serviceProviders,
            $routes,
            $data['psr4']
        );
    }
}
