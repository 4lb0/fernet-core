<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Config;
use Fernet\Core\NotFoundException;
use Fernet\Core\Router;
use Fernet\Core\Routes;
use Fernet\Tests\TestCase;

class RouterTest extends TestCase
{
    public function testDefaultRoute(): void
    {
        $routes = $this->createMock(Routes::class);
        $request = $this->createRequest();
        $routes->expects(self::once())->method('dispatch')->with(self::equalTo($request));
        $html = '<html lang="en"><body>Default route</body></html>';
        $router = new Router($this->createNullLogger(), $routes);
        self::assertEquals($html, $router->route($this->createComponent($html), $request)->getContent());
    }

    public function testRouteNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $config = new Config();
        $config->routing = ['/some-route' => 'SomeComponent'];
        $router = new Router(
            $this->createNullLogger(),
            new Routes($this->createNullLogger(), $config)
        );
        $router->route($this->createComponent(), $this->createRequest('/about'));
    }

    public function testBind(): void
    {
        $request = $this->createRequest();
        $request->request->set('fernet-bind', ['foo.bar' => 'foobar']);
        $router = new Router(
            $this->createNullLogger(),
            new Routes($this->createNullLogger(), new Config())
        );
        $component = $this->createComponent();
        $component->foo = (object) ['bar' => null];
        $router->bind($component, $request);
        self::assertEquals('foobar', $component->foo->bar);
    }

    public function testGetArgs(): void
    {
        $request = $this->createRequest();
        $request->query->set('fernet-params', ['someConfig' => serialize(false)]);
        $request->query->set('other-regular-query', 'value');
        $router = new Router(
            $this->createNullLogger(),
            new Routes($this->createNullLogger(), new Config())
        );
        self::assertCount(3, $router->getArgs($request));
    }
}
