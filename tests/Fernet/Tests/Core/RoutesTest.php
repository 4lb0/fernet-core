<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\Exception;
use Fernet\Core\Routes;
use Fernet\Framework;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RoutesTest extends TestCase
{
    public function testNoRoutesFile(): void
    {
        $framework = Framework::setUp(['routingFile' => 'file/not/exists.json']);
        $routes = new Routes($framework);
        self::assertEquals([], $routes->getRoutes());
    }

    public function testRoutesFile(): void
    {
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/routing.json']);
        $routes = (new Routes($framework))->getRoutes();
        self::assertArrayHasKey('/about', $routes);
        self::assertContains('Menu.handleAbout', $routes);
        self::assertArrayHasKey('/some/foo/bar/page', $routes);
        self::assertContains('FooBar.renderPage', $routes);
    }

    public function testRoutesFileJsonError(): void
    {
        $this->expectException(Exception::class);
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/non-json-file.json']);
        (new Routes($framework))->getRoutes();
    }

    public function testLink(): void
    {
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/routing.json']);
        $routes = new Routes($framework);
        self::assertEquals('/about', $routes->get('Menu', 'handleAbout'));
        self::assertEquals('/some/foo/bar/page', $routes->get('FooBar', 'renderPage'));
        self::assertEquals(null, $routes->get('NotMappedComponent', 'handleClick'));
        self::assertEquals('/name/John+Doe/age/75', $routes->get('UserProfile', 'show', ['name' => 'John Doe', 'age' => 75]));
    }

    public function testDispatch(): void
    {
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/routing.json']);
        $routes = new Routes($framework);
        $request = new Request();
        $request = $request->duplicate(null, null, null, null, null, ['REQUEST_URI' => '/about']);
        self::assertEquals('Menu.handleAbout', $routes->dispatch($request));

        $request = $request->duplicate(null, null, null, null, null, ['REQUEST_URI' => '/some/foo/bar/page']);
        self::assertEquals('FooBar.renderPage', $routes->dispatch($request));

        $request = new Request();
        self::assertEquals(null, $routes->dispatch($request));
    }

    public function testDefaultHandlersRoutes(): void
    {
        $framework = Framework::setUp();
        $routes = new Routes($framework);
        $request = (new Request())->duplicate(null, null, null, null, null, ['REQUEST_URI' => '/hello-component/some-handler']);
        self::assertEquals('HelloComponent.someHandler', $routes->dispatch($request));
    }

}
