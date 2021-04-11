<?php

declare(strict_types=1);

namespace Fernet\Core;

use ReflectionFunction;
use Symfony\Component\HttpFoundation\Request;

class Events
{
    private const QUERY_PARAM = '__fe';

    private int $events = 0;

    public function __construct(
        private Request $request,
        private JsBridge $jsBridge
    )
    {
    }

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
        if ($this->request->query->get(static::QUERY_PARAM) === $hash) {
            $callback();
            if ($this->request->getMethod() == 'PUT' && $this->request->getContent() == "fernet_replace") {
                $this->jsBridge->called($callback);
            }
            $this->request->query->remove(static::QUERY_PARAM);
        }
        $uri = strstr($_SERVER['REQUEST_URI'], '?', true);
        $uri .= '?'.http_build_query(array_merge($_GET, [static::QUERY_PARAM => $hash]));

        return " href=\"$uri\" ";
    }
}
