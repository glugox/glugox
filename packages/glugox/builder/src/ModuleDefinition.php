<?php

namespace Glugox\Builder;

/**
 * @phpstan-type ModuleRoute array{file: string, group: array<string, mixed>}
 */
class ModuleDefinition
{
    /**
     * @param array<int, EntityDefinition> $entities
     * @param array<int, ModuleRoute> $routes
     */
    public function __construct(
        public readonly string $name,
        public readonly string $namespace,
        public readonly array $entities,
        public readonly array $routes = []
    ) {
    }
}
