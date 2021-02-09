<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\NotFoundException;
use Fernet\Core\Router;
use Fernet\Core\Routes;
use Fernet\Framework;
use Fernet\Tests\TestCase;

class RouterTest extends TestCase
{
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

    public function testBind(): void
    {
        $framework = Framework::getInstance();
        $request = $this->createRequest();
        $request->request->set('fernet-bind', ['foo.bar' => 'foobar']);
        $router = new Router(
            $request,
            $this->createNullLogger(),
            new Routes($framework)
        );
        $component = $this->createComponent();
        $component->foo = (object) ['bar' => null];
        $router->bind($component);
        self::assertEquals('foobar', $component->foo->bar);
    }

    public function testGetArgs(): void
    {
        $framework = Framework::getInstance();
        $request = $this->createRequest();
        $request->query->set('fernet-params', ['someConfig' => serialize(false)]);
        $request->query->set('other-regular-query', 'value');
        $router = new Router(
            $request,
            $this->createNullLogger(),
            new Routes($framework)
        );
        self::assertCount(3, $router->getArgs());
    }

}
