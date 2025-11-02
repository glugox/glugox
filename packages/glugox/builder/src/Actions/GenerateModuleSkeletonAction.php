<?php

namespace Glugox\Builder\Actions;

use Glugox\Builder\Blueprint\ModuleBlueprint;
use Glugox\Builder\Blueprint\RouteBlueprint;
use RuntimeException;

class GenerateModuleSkeletonAction
{
    /**
     * @var callable|null
     */
    private $logger;

    public function __construct(?callable $logger = null)
    {
        $this->logger = $logger;
    }

    public function __invoke(ModuleBlueprint $blueprint, string $destination): void
    {
        $slug = trim($blueprint->settings->slug);

        if ($slug === '') {
            throw new RuntimeException('Module slug is required to generate the module skeleton.');
        }

        $moduleName = $this->studly($slug);
        $namespace = 'Glugox\\' . $moduleName;
        $providerClass = $moduleName . 'ServiceProvider';

        $this->log("Preparing module skeleton for [{$slug}] in {$destination}");

        $this->ensureDirectory($destination);
        $this->ensureDirectory($destination . '/src');
        $this->ensureDirectory($destination . '/src/Http/Controllers');
        $this->ensureDirectory($destination . '/routes');

        $this->writeComposerJson($destination, $blueprint, $slug, $namespace, $providerClass);
        $this->writeServiceProvider($destination, $namespace, $providerClass);
        $this->writeRoutesFile($destination, $blueprint, $namespace);
        $this->writeControllers($destination, $blueprint, $namespace);
    }

    private function writeComposerJson(string $destination, ModuleBlueprint $blueprint, string $slug, string $namespace, string $providerClass): void
    {
        $path = $destination . '/composer.json';

        if (file_exists($path)) {
            $this->log("composer.json already exists for [{$slug}], skipping.");

            return;
        }

        $description = $blueprint->settings->description
            ?? ($blueprint->settings->name !== ''
                ? $blueprint->settings->name . ' module'
                : 'Generated Glugox module');

        $contents = json_encode([
            'name' => 'glugox/' . $slug,
            'description' => $description,
            'type' => 'library',
            'require' => [
                'php' => '^8.1',
                'glugox/module' => 'dev-main',
            ],
            'autoload' => [
                'psr-4' => [
                    $namespace . '\\' => 'src/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        $namespace . '\\' . $providerClass,
                    ],
                ],
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        file_put_contents($path, $contents);
        $this->log('Created composer.json');
    }

    private function writeServiceProvider(string $destination, string $namespace, string $providerClass): void
    {
        $path = $destination . '/src/' . $providerClass . '.php';

        if (file_exists($path)) {
            $this->log("Service provider {$providerClass} already exists, skipping.");

            return;
        }

        $contents = <<<PHP
<?php

namespace {$namespace};

use Glugox\Module\BaseModuleServiceProvider;

class {$providerClass} extends BaseModuleServiceProvider
{
    public function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }
}

PHP;

        file_put_contents($path, $contents);
        $this->log('Created service provider');
    }

    private function writeRoutesFile(string $destination, ModuleBlueprint $blueprint, string $namespace): void
    {
        $path = $destination . '/routes/api.php';

        if (file_exists($path)) {
            $this->log('routes/api.php already exists, skipping.');

            return;
        }

        if ($blueprint->routes === []) {
            $this->log('No routes defined in blueprint. routes/api.php will not be created.');

            return;
        }

        $imports = [];
        $definitions = [];

        foreach ($blueprint->routes as $route) {
            $controllerData = $this->parseAction($route, $namespace);
            $imports[$controllerData['class']] = 'use ' . $controllerData['class'] . ';';

            $definitions[] = sprintf(
                "        Route::%s('%s', [%s::class, '%s']);",
                strtolower($route->method),
                ltrim($route->uri, '/'),
                $controllerData['short'],
                $controllerData['method']
            );
        }

        $importsBlock = $this->implodeLines($imports);
        $definitionsBlock = $this->implodeLines($definitions);

        $contents = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
{$importsBlock}Route::middleware('api')
    ->group(function (): void {
{$definitionsBlock}    });

PHP;

        file_put_contents($path, $contents);
        $this->log('Created routes/api.php');
    }

    private function writeControllers(string $destination, ModuleBlueprint $blueprint, string $namespace): void
    {
        $controllerMethods = [];

        foreach ($blueprint->routes as $route) {
            $controllerData = $this->parseAction($route, $namespace);
            $controllerMethods[$controllerData['short']][] = $controllerData['method'];
        }

        foreach ($controllerMethods as $controller => $methods) {
            $path = $destination . '/src/Http/Controllers/' . $controller . '.php';

            if (file_exists($path)) {
                $this->log("Controller {$controller} already exists, skipping.");

                continue;
            }

            $methodBodies = array_map(fn (string $method): string => $this->controllerMethod($method), array_unique($methods));
            $methodsBlock = $this->implodeLines($methodBodies);

            $contents = <<<PHP
<?php

namespace {$namespace}\Http\Controllers;

class {$controller}
{
{$methodsBlock}}

PHP;

            file_put_contents($path, $contents);
            $this->log("Created controller {$controller}");
        }
    }

    private function parseAction(RouteBlueprint $route, string $namespace): array
    {
        $action = $route->action;

        if (! str_contains($action, '@')) {
            throw new RuntimeException("Route action [{$action}] is not in 'Controller@method' format.");
        }

        [$controller, $method] = explode('@', $action, 2);
        $controller = trim($controller);
        $method = trim($method);

        if ($controller === '' || $method === '') {
            throw new RuntimeException("Invalid route action [{$action}].");
        }

        $class = $namespace . '\\Http\\Controllers\\' . $controller;

        return [
            'short' => $controller,
            'class' => $class,
            'method' => $method,
        ];
    }

    private function controllerMethod(string $method): string
    {
        $body = match ($method) {
            'index' => "        return response()->json([]);",
            default => "        return response()->json(['status' => 'ok']);",
        };

        return <<<PHP
    public function {$method}()
    {
{$body}
    }

PHP;
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
            $this->log("Created directory {$path}");
        }
    }

    private function implodeLines(array $lines): string
    {
        if ($lines === []) {
            return '';
        }

        return implode("\n", array_values($lines)) . "\n";
    }

    private function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', strtolower($value));

        return str_replace(' ', '', ucwords($value));
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }
}
