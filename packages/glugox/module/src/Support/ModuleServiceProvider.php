<?php

namespace Glugox\Module\Support;

use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Return the path that contains the module's migration files.
     */
    protected function migrationPath(): ?string
    {
        return null;
    }

    /**
     * Boot the module by loading migrations if provided.
     */
    public function boot(): void
    {
        if (method_exists($this, 'loadMigrationsFrom')) {
            $path = $this->migrationPath();
            if ($path && is_dir($path)) {
                $this->loadMigrationsFrom($path);
            }
        }
    }
}
