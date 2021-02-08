<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\NotFoundException;
use Fernet\Core\Router;
use Fernet\Core\Routes;
use Fernet\Framework;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Stringable;
use Symfony\Component\HttpFoundation\Request;

class RouterTest extends TestCase
{
    private function createRequest(string $url = '/'): Request
    {
        return (new Request())->duplicate(null, null, null, null, null, ['REQUEST_URI' => $url]);
    }

    private function createNullLogger(): Logger
    {
        $log = new Logger('test');
        $log->setHandlers([new NullHandler()]);

        return $log;
    }

    private function createComponent(?string $content = null): Stringable
    {
        if (!$content) {
            $content = substr(str_shuffle(md5(microtime())), 0, 10);
        }
        $component = $this->createMock(Stringable::class);
        $component->method('__toString')->willReturn($content);

        return $component;
    }

    public function testDefaultRoute(): void
    {
        $routes = $this->createMock(Routes::class);
        $request = $this->createRequest();
        $routes->expects(self::once())->method('dispatch')->with(self::equalTo($request));
        $html = '<html lang="en"><body>Default route</body></html>';
        $router = new Router($request, $this->createNullLogger(), $routes);
        self::assertEquals($html, $router->route($this->createComponent($html))->getContent());
    }

    public function testRouteNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/routing.json']);
        $router = new Router(
            $this->createRequest('/about'),
            $this->createNullLogger(),
            new Routes($framework)
        );
        $router->route($this->createComponent());
    }
}
