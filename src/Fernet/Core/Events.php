<?php

declare(strict_types=1);

namespace Fernet\Core;

class Events
{
    private const QUERY_PARAM = '__fe';

    private int $events = 0;

    public function hash(...$params): string
    {
        return substr(md5(serialize($params)), -7);
    }

    public function getLastEvent(): int
    {
        return $this->events;
    }

    public function restore(int $events): void
    {
        $this->events = $events;
    }

    public function onClick(callable $callback, $unique = null): string
    {
        $hash = $this->hash(++$this->events, $unique);
        if (isset($_GET[static::QUERY_PARAM])) {
            if ($hash === $_GET[static::QUERY_PARAM]) {
                $callback();
                unset($_GET[static::QUERY_PARAM]);
            }
        }
        $uri = strstr($_SERVER['REQUEST_URI'], '?', true);
        $uri .= '?'.http_build_query(array_merge($_GET, [static::QUERY_PARAM => $hash]));

        return " href=\"$uri\" ";
    }
}
