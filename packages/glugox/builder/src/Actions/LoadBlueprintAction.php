<?php

namespace Glugox\Builder\Actions;

use Glugox\Builder\Blueprint\ModuleBlueprint;
use RuntimeException;

class LoadBlueprintAction
{
    /**
     * @var callable|null
     */
    private $logger;

    public function __construct(?callable $logger = null)
    {
        $this->logger = $logger;
    }

    public function __invoke(string $path): ModuleBlueprint
    {
        $this->log("Reading blueprint from {$path}");

        if (! is_file($path)) {
            throw new RuntimeException("Blueprint file not found: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Failed to read blueprint file: {$path}");
        }

        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        $blueprint = ModuleBlueprint::fromArray($data);

        $this->log(sprintf(
            'Loaded module "%s" with %d entities and %d route(s).',
            $blueprint->settings->name,
            count($blueprint->entities),
            count($blueprint->routes)
        ));

        return $blueprint;
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            ($this->logger)($message);
            return;
        }

        // Minimal default logging to STDOUT for now.
        echo "[Builder] {$message}\n";
    }
}
