<?php

namespace Glugox\Builder;

use Glugox\Builder\Support\Str;
use InvalidArgumentException;

class ModuleBuilder
{
    public function __construct(
        private readonly string $modulesPath
    ) {
    }

    public function buildFromFile(string $definitionFile): ModuleDefinition
    {
        if (!is_file($definitionFile)) {
            throw new InvalidArgumentException("Definition file {$definitionFile} does not exist.");
        }

        $data = json_decode(file_get_contents($definitionFile), true, 512, JSON_THROW_ON_ERROR);

        $definition = $this->parseDefinition($data);
        $this->scaffold($definition);

        return $definition;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parseDefinition(array $data): ModuleDefinition
    {
        $name = $data['name'] ?? $data['module'] ?? null;
        if (!$name) {
            throw new InvalidArgumentException('Module definition is missing a name.');
        }

        $namespace = $data['namespace'] ?? 'Modules\\' . Str::studly($name);
        $entities = [];
        foreach ($data['entities'] ?? [] as $entity) {
            if (!isset($entity['name'], $entity['fields'])) {
                throw new InvalidArgumentException('Each entity requires a name and a fields definition.');
            }

            $entities[] = new EntityDefinition($entity['name'], $entity['fields']);
        }

        if (empty($entities)) {
            throw new InvalidArgumentException('At least one entity definition is required.');
        }

        $routes = $data['routes'] ?? [];
        if (empty($routes)) {
            $routes[] = [
                'file' => 'routes/api.php',
                'group' => [
                    'prefix' => Str::snake($name),
                    'middleware' => ['api'],
                ],
            ];
        }

        $normalisedRoutes = [];
        foreach ($routes as $route) {
            if (!isset($route['file'])) {
                throw new InvalidArgumentException('Each route definition requires a file attribute.');
            }

            $group = $route['group'] ?? [];
            if (!is_array($group)) {
                $group = [];
            }

            $normalisedRoutes[] = [
                'file' => $route['file'],
                'group' => $group,
            ];
        }

        return new ModuleDefinition($name, $namespace, $entities, $normalisedRoutes);
    }

    private function scaffold(ModuleDefinition $definition): void
    {
        $moduleStudly = Str::studly($definition->name);
        $baseNamespace = rtrim($definition->namespace, '\\');
        $modulePath = rtrim($this->modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleStudly;

        $paths = [
            'src',
            'src/Models',
            'src/Http/Controllers',
            'src/Providers',
            'database/migrations',
            'routes',
        ];

        foreach ($paths as $path) {
            $fullPath = $modulePath . DIRECTORY_SEPARATOR . $path;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0777, true);
            }
        }

        $registrarClass = $baseNamespace . '\\' . $moduleStudly . 'Registrar';
        $serviceProviderClass = $baseNamespace . '\\Providers\\' . $moduleStudly . 'ServiceProvider';

        $packageName = 'glugox/module-' . Str::snake($definition->name);

        $this->writeModuleComposer($modulePath, $baseNamespace, $packageName);
        $this->writeModuleManifest($modulePath, $definition, $registrarClass, $serviceProviderClass);
        $this->writeRegistrar($modulePath, $baseNamespace, $registrarClass, $definition);
        $this->writeServiceProvider($modulePath, $baseNamespace, $serviceProviderClass);
        $this->writeRoutes($modulePath, $baseNamespace, $definition);
        $this->writeEntities($modulePath, $baseNamespace, $definition);
    }

    private function writeModuleComposer(string $modulePath, string $namespace, string $packageName): void
    {
        $composer = [
            'name' => $packageName,
            'type' => 'library',
            'autoload' => [
                'psr-4' => [
                    rtrim($namespace, '\\') . '\\' => 'src/',
                ],
            ],
        ];

        $this->writeJson($modulePath . '/composer.json', $composer);
    }

    private function writeModuleManifest(string $modulePath, ModuleDefinition $definition, string $registrarClass, string $serviceProviderClass): void
    {
        $manifest = [
            'name' => $definition->name,
            'namespace' => $definition->namespace,
            'registrar' => $registrarClass,
            'service_providers' => [$serviceProviderClass],
            'routes' => $definition->routes,
            'psr4' => [
                rtrim($definition->namespace, '\\') . '\\' => 'src/',
            ],
        ];

        $this->writeJson($modulePath . '/module.json', $manifest);
    }

    private function writeRegistrar(string $modulePath, string $namespace, string $registrarClass, ModuleDefinition $definition): void
    {
        $className = basename(str_replace('\\', '/', $registrarClass));
        $serviceProvider = $namespace . '\\Providers\\' . Str::studly($definition->name) . 'ServiceProvider';

        $routeDefinitions = [];
        foreach ($definition->routes as $route) {
            $routeDefinitions[] = sprintf(
                "            ['group' => %s, 'file' => __DIR__ . '/../%s'],",
                $this->exportArray($route['group']),
                $route['file']
            );
        }
        $routeDefinitions = implode("\n", $routeDefinitions);

        $content = <<<PHP
<?php

namespace {$namespace};

use Glugox\\Module\\Support\\AbstractModuleRegistrar;

class {$className} extends AbstractModuleRegistrar
{
    protected function serviceProviders(): array
    {
        return [
            {$serviceProvider}::class,
        ];
    }

    protected function routeDefinitions(): array
    {
        return [
{$routeDefinitions}
        ];
    }
}
PHP;

        $this->writeFile($modulePath . '/src/' . $className . '.php', $content);
    }

    private function writeServiceProvider(string $modulePath, string $namespace, string $serviceProviderClass): void
    {
        $className = basename(str_replace('\\', '/', $serviceProviderClass));
        $content = <<<PHP
<?php

namespace {$namespace}\\Providers;

use Glugox\\Module\\Support\\ModuleServiceProvider;

class {$className} extends ModuleServiceProvider
{
    protected function migrationPath(): ?string
    {
        return __DIR__ . '/../../database/migrations';
    }
}
PHP;

        $this->writeFile($modulePath . '/src/Providers/' . $className . '.php', $content);
    }

    private function writeRoutes(string $modulePath, string $namespace, ModuleDefinition $definition): void
    {
        $uses = [];
        $routesBody = [];
        foreach ($definition->entities as $entity) {
            $controllerClass = $namespace . '\\Http\\Controllers\\' . Str::studly($entity->name) . 'Controller';
            $uses[$controllerClass] = $controllerClass;
            $routesBody[] = sprintf("Route::apiResource('%s', %s::class);", Str::snake($entity->name), class_basename($controllerClass));
        }

        $usesLines = array_map(
            fn(string $class) => 'use ' . $class . ';',
            array_values($uses)
        );
        $usesLines = implode("\n", $usesLines);
        $routesBody = implode("\n", $routesBody);

        $content = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
{$usesLines}

{$routesBody}
PHP;

        $this->writeFile($modulePath . '/routes/api.php', $content);
    }

    private function writeEntities(string $modulePath, string $namespace, ModuleDefinition $definition): void
    {
        $timestamp = time();
        foreach ($definition->entities as $index => $entity) {
            $studly = Str::studly($entity->name);
            $this->writeModel($modulePath, $namespace, $entity, $studly);
            $this->writeController($modulePath, $namespace, $entity, $studly);
            $this->writeMigration($modulePath, $namespace, $entity, $timestamp + $index);
        }
    }


    private function writeModel(string $modulePath, string $namespace, EntityDefinition $entity, string $studly): void
    {
        $fillable = [];
        foreach ($entity->fields as $field) {
            $type = $field['type'] ?? null;
            $name = $field['name'] ?? null;
            if (!$type || !$name) {
                continue;
            }

            if (in_array($type, ['id', 'timestamps', 'softDeletes'], true)) {
                continue;
            }

            $fillable[] = $name;
        }

        $fillableLines = implode(",
            ", array_map(fn(string $field) => var_export($field, true), $fillable));

        $content = <<<PHP
<?php

namespace {$namespace}\Models;

use Illuminate\Database\Eloquent\Model;

class {$studly} extends Model
{
    protected $fillable = [
            {$fillableLines}
    ];
}
PHP;

        $this->writeFile($modulePath . '/src/Models/' . $studly . '.php', $content);
    }

    private function writeController(string $modulePath, string $namespace, EntityDefinition $entity, string $studly): void
    {
        $modelClass = $namespace . '\\Models\\' . $studly;
        $varName = '$' . Str::snake($entity->name);

        $content = <<<PHP
<?php

namespace {$namespace}\Http\Controllers;

use {$modelClass};
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class {$studly}Controller extends Controller
{
    public function index()
    {
        return {$modelClass}::query()->paginate();
    }

    public function store(Request $request)
    {
        $data = $request->all();

        return {$modelClass}::query()->create($data);
    }

    public function show({$modelClass} {$varName})
    {
        return {$varName};
    }

    public function update(Request $request, {$modelClass} {$varName})
    {
        {$varName}->fill($request->all());
        {$varName}->save();

        return {$varName};
    }

    public function destroy({$modelClass} {$varName})
    {
        {$varName}->delete();

        return response()->noContent();
    }
}
PHP;

        $this->writeFile($modulePath . '/src/Http/Controllers/' . $studly . 'Controller.php', $content);
    }

    private function writeMigration(string $modulePath, string $namespace, EntityDefinition $entity, int $timestamp): void
    {
        $table = Str::snake($entity->name) . 's';
        $filename = date('Y_m_d_His', $timestamp) . '_create_' . $table . '_table.php';
        $columns = [];
        foreach ($entity->fields as $field) {
            $columns[] = $this->buildMigrationColumn($field);
        }

        if (!in_array('$table->timestamps();', $columns, true)) {
            $columns[] = '$table->timestamps();';
        }

        $columns = implode("
            ", array_filter($columns));

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint $table) {
            {$columns}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;

        $this->writeFile($modulePath . '/database/migrations/' . $filename, $content);
    }
    private function buildMigrationColumn(array $field): ?string
    {
        $type = $field['type'] ?? null;
        $name = $field['name'] ?? null;
        $nullable = (bool)($field['nullable'] ?? false);
        $unique = (bool)($field['unique'] ?? false);
        $arguments = $field['arguments'] ?? [];

        if (!$type) {
            return null;
        }

        if (in_array($type, ['id', 'timestamps', 'softDeletes'], true)) {
            $column = "$table->{$type}();";
        } else {
            if (!$name) {
                throw new InvalidArgumentException('Field name is required for column type ' . $type);
            }

            $args = array_merge([$name], is_array($arguments) ? $arguments : []);
            $column = sprintf('$table->%s(%s)', $type, $this->exportArguments($args));
            if ($nullable) {
                $column .= '->nullable()';
            }
            if ($unique) {
                $column .= '->unique()';
            }
            $column .= ';';
        }

        return $column;
    }

    private function exportArray(array $value): string
    {
        return str_replace("\n", '', var_export($value, true));
    }

    private function exportArguments(array $arguments): string
    {
        return implode(', ', array_map(static fn($argument) => var_export($argument, true), $arguments));
    }

    private function writeJson(string $path, array $data): void
    {
        $this->writeFile($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $contents);
    }
}

if (!function_exists('class_basename')) {
    function class_basename(string $class): string
    {
        $class = trim($class, "\\");

        if ($pos = strrpos($class, "\\")) {
            return substr($class, $pos + 1);
        }

        return $class;
    }
}
