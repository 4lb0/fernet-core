<?php
declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\Router;
use Fernet\Core\Routes;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RouterTest extends TestCase
{
    public function testDefaultRoute(): void
    {
        $request = new Request();
        $log = new Logger('test');
        $log->setHandlers([new NullHandler()]);
        $routes = $this->createMock(Routes::class);
        $routes->expects($this->once())->method('dispatch')->with($this->equalTo($request));
        $html = '<html lang="en"><body>Default route</body></html>';
        $defaultRoute = $this->createMock(\Stringable::class);
        $defaultRoute->method('__toString')->willReturn($html);
        $router = new Router($request, $log, $routes);
        self::assertEquals($html, $router->route($defaultRoute)->getContent());
    }
}
