<?php

declare(strict_types=1);

namespace Fernet\Core;

class CaseConverter
{
    public static function camelCase(string $string): string
    {
        return lcfirst(static::pascalCase($string));
    }

    public static function pascalCase(string $string): string
    {
        $string = str_replace('.', ' \\ ', $string);
        $string = str_replace(['-', '_'], ' ', strtolower($string));
        return str_replace(' ', '', ucwords($string));
    }

    public static function kebab(string $string): string
    {
        $string = str_replace('\\', '.', $string);
        $string = preg_replace('/([A-Z.])/', '-\1', $string);
        $string = trim($string, '-');
        $string = str_replace('-.-', '.', $string);

        return strtolower($string);
    }
}
