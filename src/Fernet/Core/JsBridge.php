<?php

declare(strict_types=1);

namespace Fernet\Core;

use Monolog\Logger;
use ReflectionException;
use ReflectionFunction;
use Stringable;
use Symfony\Component\HttpFoundation\Response;

class JsBridge
{
    private ?Stringable $calledComponent = null;

    public function __construct(private Logger $log)
    {
    }

    public function called(callable $callback): void
    {
        try {
            $component = (new ReflectionFunction($callback))->getClosureThis();
            $this->calledComponent = $component;
        } catch (ReflectionException $e) {
            $this->log->error("Can't use reflection on callback: ".$e->getMessage());
        }
    }

    public function setRoute(Stringable $component): void
    {
        $this->calledComponent = $component;
    }

    public function setContent(Stringable $component, string $content): void
    {
        if ($this->calledComponent === $component) {
            // FIXME: Refactor this is imposible to test, we should return a response somewhere
            $this->log->debug('Callback content finish rendered');
            $response = new Response($content);
            $response->send();
            exit;
        }
    }
}
