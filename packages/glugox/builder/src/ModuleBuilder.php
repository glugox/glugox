<?php

namespace Glugox\Builder;

use Glugox\Builder\Actions\LoadBlueprintAction;
use Glugox\Builder\Blueprint\ModuleBlueprint;

class ModuleBuilder
{
    public function __construct(
        private readonly LoadBlueprintAction $loadBlueprint,
    ) {
    }

    public function loadFromFile(string $path): ModuleBlueprint
    {
        return ($this->loadBlueprint)($path);
    }
}
