<?php

declare(strict_types=1);

namespace Fernet\Core;

use Exception;
use Symfony\Component\HttpFoundation\Request;

class Events
{
    private const RANDOM_LENGTH = 64;
    private const QUERY_PARAM = '__fe';
    private string $random;

    private int $events = 0;
    private array $callbacks = [];

    public function __construct(private Request $request)
    {
        try {
            $this->random = bin2hex(random_bytes(static::RANDOM_LENGTH / 2));
        } catch (Exception) {
            $this->random = md5((string) mt_rand());
        }
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
            $this->request->query->remove(static::QUERY_PARAM);
        }
        $uri = strstr($_SERVER['REQUEST_URI'], '?', true);
        $uri .= '?'.http_build_query(array_merge($_GET, [static::QUERY_PARAM => $hash]));

        return " href=\"$uri\" ";
    }

    public function onReady(callable $callback): string
    {
        $id = count($this->callbacks);
        $hash = $this->callbackHash($id);
        $this->callbacks[$hash] = $callback;

        return $hash;
    }

    private function callbackHash(string | int $id): string
    {
        return "##FERNET_CALLBACK#$id#$this->random##";
    }

    public function replaceCallbacks(string $content): string
    {
        return str_replace(
            array_keys($this->callbacks),
            array_map(static fn ($callback) => $callback(), $this->callbacks),
            $content
        );
    }

    public function call($id)
    {
        return $this->callbacks[$id];
    }
}
