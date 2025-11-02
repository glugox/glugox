<?php

declare(strict_types=1);

$scriptDir = __DIR__;
$repoRoot = dirname($scriptDir);
$builderSrc = $repoRoot . '/packages/glugox/builder/src';

spl_autoload_register(static function (string $class) use ($builderSrc): void {
    $prefix = 'Glugox\\Builder\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = $builderSrc . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($relativePath)) {
        require_once $relativePath;
    }
});

$logger = static function (string $message): void {
    fwrite(STDOUT, "[Builder] {$message}\n");
};

$builder = new Glugox\Builder\ModuleBuilder(
    new Glugox\Builder\Actions\LoadBlueprintAction($logger),
    new Glugox\Builder\Actions\GenerateModuleSkeletonAction($logger),
);

$blueprintDir = $scriptDir . '/specs';
$modulesDir = $scriptDir . '/modules';

if (! is_dir($blueprintDir)) {
    fwrite(STDERR, "Blueprint directory not found: {$blueprintDir}\n");
    exit(1);
}

if (! is_dir($modulesDir)) {
    mkdir($modulesDir, 0775, true);
    $logger("Created modules directory at {$modulesDir}");
}

$blueprintFiles = glob($blueprintDir . '/*.json');

if ($blueprintFiles === false) {
    fwrite(STDERR, "Failed to read blueprint directory: {$blueprintDir}\n");
    exit(1);
}

if ($blueprintFiles === []) {
    $logger('No blueprint files discovered. Nothing to build.');
    exit(0);
}

foreach ($blueprintFiles as $blueprintFile) {
    $blueprint = $builder->loadFromFile($blueprintFile);

    $slug = trim($blueprint->settings->slug);

    if ($slug === '') {
        $logger("Blueprint {$blueprintFile} is missing a slug. Skipping.");
        continue;
    }

    $modulePath = $modulesDir . '/' . $slug;

    if (is_file($modulePath . '/composer.json')) {
        $logger("Module [{$slug}] already built. Skipping.");
        continue;
    }

    $logger("Building module [{$slug}] from blueprint {$blueprintFile}.");
    $builder->build($blueprint, $modulePath);
}
