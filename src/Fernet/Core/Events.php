<?php

declare(strict_types=1);

namespace Fernet\Core;

use Symfony\Component\HttpFoundation\Request;

class Events
{
    private const QUERY_PARAM = '__fe';

    private int $events = 0;

    public function __construct(
        private Request $request,
        private JsBridge $jsBridge
    ) {
    }

    /**
     * @param mixed ...$params
     */
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
            if ('PUT' === $this->request->getMethod() && 'fernet_replace' === $this->request->getContent()) {
                $this->jsBridge->called($callback);
            }
            $this->request->query->remove(static::QUERY_PARAM);
        }
        $uri = strstr($_SERVER['REQUEST_URI'], '?', true);
        $uri .= '?'.http_build_query(array_merge($_GET, [static::QUERY_PARAM => $hash]));

        return " href=\"$uri\" ";
    }
}
