<?php

declare(strict_types=1);

namespace Fernet\Core;

class Events
{
    public const QUERY_PARAM = '__fe';

    private static int $events = 0;

    public static function hash($hash): string
    {
        return substr(md5(serialize($hash)), -7);
    }

    public static function getLastEvent(): int
    {
        return static::$events;
    }

    public static function restore(int $events): void
    {
        static::$events = $events;
    }

    public static function onClick(callable $callback, $unique = null): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        $uri = strstr($uri, '?', true);
        $id = ++static::$events;
        $hash = static::hash($unique.$id);
        if (isset($_GET[static::QUERY_PARAM])) {
            [$getId, $getHash] = explode('.', $_GET[static::QUERY_PARAM]);
            $getId = (int) $getId;
            if ($id === $getId) {
                if ($hash === $getHash) {
                    $callback();
                    unset($_GET[static::QUERY_PARAM]);
                } else {
                    new Exception("Callback can't be validated");
                }
            }
        }
        $uri .= '?'.http_build_query(array_merge($_GET, [static::QUERY_PARAM => "$id.$hash"]));

        return " href=\"$uri\" ";
    }
}
