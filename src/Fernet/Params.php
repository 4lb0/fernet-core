<?php

declare(strict_types=1);

namespace Fernet;

use function count;
use function get_class;

class Params
{
    /**
     * @var array Objects are saving here so we can pass them as text
     */
    private static array $objects = [];

    /**
     * Prints the dynamic params passed to the component.
     * @param array $params
     * @return string
     */
    public static function component(array $params): string
    {
        $outputParams = [];
        foreach ($params as $key => $value) {
            $class = is_object($value) ? get_class($value) : strtolower(gettype($value));
            $name = static::set($class, $value);
            $outputParams[] = "$key={".$name.'}';
        }

        return implode(' ', $outputParams);
    }

    /**
     * Prints the dynamic values passed to the events.
     * @param array $args
     * @return string
     */
    public static function event(array $args = []): string
    {
        return htmlentities(serialize($args));
    }

    public static function set(string $key, $value): string
    {
        $position = count(static::$objects);
        $name = "$key#$position";
        static::$objects[$name] = $value;

        return $name;
    }

    public static function get(string $key)
    {
        return static::$objects[$key];
    }
}
