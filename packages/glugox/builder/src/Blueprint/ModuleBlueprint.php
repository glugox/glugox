<?php

namespace Glugox\Builder\Blueprint;

/**
 * Data transfer object representing a module blueprint definition.
 */
class ModuleBlueprint
{
    /**
     * @param ModuleSettings $settings
     * @param EntityBlueprint[] $entities
     * @param RouteBlueprint[] $routes
     */
    public function __construct(
        public readonly ModuleSettings $settings,
        public readonly array $entities,
        public readonly array $routes,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $settings = ModuleSettings::fromArray($data['settings'] ?? []);

        $entities = array_map(
            static fn (array $entity): EntityBlueprint => EntityBlueprint::fromArray($entity),
            $data['entities'] ?? []
        );

        $routes = array_map(
            static fn (array $route): RouteBlueprint => RouteBlueprint::fromArray($route),
            $data['routes'] ?? []
        );

        return new self($settings, $entities, $routes);
    }
}

class ModuleSettings
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $version,
        public readonly ?string $description = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            slug: (string) ($data['slug'] ?? ''),
            version: (string) ($data['version'] ?? '0.1.0'),
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
    }
}

class EntityBlueprint
{
    /**
     * @param FieldBlueprint[] $fields
     */
    public function __construct(
        public readonly string $name,
        public readonly array $fields,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $fields = array_map(
            static fn (array $field): FieldBlueprint => FieldBlueprint::fromArray($field),
            $data['fields'] ?? []
        );

        return new self((string) ($data['name'] ?? ''), $fields);
    }
}

class FieldBlueprint
{
    /**
     * @param string[] $rules
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly array $rules = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rules = array_map('strval', $data['rules'] ?? []);

        return new self(
            name: (string) ($data['name'] ?? ''),
            type: (string) ($data['type'] ?? 'string'),
            rules: $rules,
        );
    }
}

class RouteBlueprint
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly string $action,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            method: (string) ($data['method'] ?? 'get'),
            uri: (string) ($data['uri'] ?? ''),
            action: (string) ($data['action'] ?? ''),
        );
    }
}
