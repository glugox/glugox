<?php

namespace Glugox\Core\Support;

class Psr4Autoloader
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $prefixes = [];

    private bool $registered = false;

    public function register(): void
    {
        if (!$this->registered) {
            spl_autoload_register([$this, 'loadClass']);
            $this->registered = true;
        }
    }

    public function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->prefixes[$prefix][] = $baseDir;
    }

    private function loadClass(string $class): void
    {
        $prefix = $class;

        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);

            if ($this->loadMappedFile($prefix, $relativeClass)) {
                return;
            }

            $prefix = rtrim($prefix, '\\');
        }
    }

    private function loadMappedFile(string $prefix, string $relativeClass): bool
    {
        if (!isset($this->prefixes[$prefix])) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
            if (is_file($file)) {
                require $file;
                return true;
            }
        }

        return false;
    }
}
