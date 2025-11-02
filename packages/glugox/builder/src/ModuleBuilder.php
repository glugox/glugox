<?php

namespace Glugox\Builder;

use Glugox\Builder\Actions\GenerateModuleSkeletonAction;
use Glugox\Builder\Actions\LoadBlueprintAction;
use Glugox\Builder\Blueprint\ModuleBlueprint;

class ModuleBuilder
{
    public function __construct(
        private readonly LoadBlueprintAction $loadBlueprint,
        private readonly GenerateModuleSkeletonAction $generateModuleSkeleton,
    ) {
    }

    public function loadFromFile(string $path): ModuleBlueprint
    {
        return ($this->loadBlueprint)($path);
    }

    public function buildFromFile(string $path, string $destination): ModuleBlueprint
    {
        $blueprint = $this->loadFromFile($path);
        $this->build($blueprint, $destination);

        return $blueprint;
    }

    public function build(ModuleBlueprint $blueprint, string $destination): void
    {
        ($this->generateModuleSkeleton)($blueprint, $destination);
    }
}
