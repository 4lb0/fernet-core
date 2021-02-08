<?php

declare(strict_types=1);

namespace Fernet\Tests;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Stringable;
use Symfony\Component\HttpFoundation\Request;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function createRequest(string $url = '/'): Request
    {
        return (new Request())->duplicate(null, null, null, null, null, ['REQUEST_URI' => $url]);
    }

    protected function createNullLogger(): Logger
    {
        $log = new Logger('test');
        $log->setHandlers([new NullHandler()]);

        return $log;
    }

    protected function createComponent(?string $content = null): Stringable
    {
        if (!$content) {
            $content = substr(str_shuffle(md5(microtime())), 0, 10);
        }
        $component = $this->createMock(Stringable::class);
        $component->method('__toString')->willReturn($content);

        return $component;
    }

}
