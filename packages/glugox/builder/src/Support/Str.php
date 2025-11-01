<?php

namespace Glugox\Builder\Support;

class Str
{
    public static function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }

    public static function snake(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $value = preg_replace('/(.)(?=[A-Z])/u', '$1_', $value);
        $value = strtolower(str_replace([' ', '-'], '_', $value));

        return $value;
    }
}
