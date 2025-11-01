<?php

namespace Glugox\Builder;

class EntityDefinition
{
    /**
     * @param array<int, array{name: string, type: string, nullable?: bool}> $fields
     */
    public function __construct(
        public readonly string $name,
        public readonly array $fields
    ) {
    }
}
