<?php

namespace Glugox\Module\Contracts;

interface ModuleRegistrar
{
    public function register(ModuleRegistry $registry): void;
}
