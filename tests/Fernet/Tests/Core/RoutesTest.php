<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\Exception;
use Fernet\Core\Routes;
use Fernet\Framework;
use Fernet\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RoutesTest extends TestCase
{
    public function testNoRoutesFile(): void
    {
        $framework = Framework::setUp(['routingFile' => 'file/not/exists.json']);
        $routes = new Routes($framework, $this->createNullLogger());
        self::assertEquals([], $routes->getRoutes());
    }

    public function testRoutesFile(): void
    {
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/routing.json']);
        $routes = (new Routes($framework, $this->createNullLogger()))->getRoutes();
        self::assertArrayHasKey('/about', $routes);
        self::assertContains('Menu.handleAbout', $routes);
        self::assertArrayHasKey('/some/foo/bar/page', $routes);
        self::assertContains('FooBar.renderPage', $routes);
    }

    public function testRoutesFileJsonError(): void
    {
        $this->expectException(Exception::class);
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/non-json-file.txt']);
        (new Routes($framework, $this->createNullLogger()))->getRoutes();
    }

    public function testLink(): void
    {
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/routing.json']);
        $routes = new Routes($framework, $this->createNullLogger());
        self::assertEquals('/about', $routes->get('Menu', 'handleAbout'));
        self::assertEquals('/some/foo/bar/page', $routes->get('FooBar', 'renderPage'));
        self::assertEquals('/not-mapped-component/handle-click', $routes->get('NotMappedComponent', 'handleClick'));
        self::assertEquals('/name/John+Doe/age/75', $routes->get('UserProfile', 'show', ['name' => 'John Doe', 'age' => 75]));
    }

    public function testDispatch(): void
    {
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/routing.json']);
        $routes = new Routes($framework, $this->createNullLogger());
        $request = $this->createRequest('/about');
        self::assertEquals('Menu.handleAbout', $routes->dispatch($request));

        $request = $this->createRequest('/some/foo/bar/page');
        self::assertEquals('FooBar.renderPage', $routes->dispatch($request));

        $request = new Request();
        self::assertEquals(null, $routes->dispatch($request));
    }

    public function testDefaultHandlersRoutes(): void
    {
        $framework = Framework::setUp();
        $routes = new Routes($framework, $this->createNullLogger());
        $request = $this->createRequest('/hello-component/some-handler');
        self::assertEquals('HelloComponent.someHandler', $routes->dispatch($request));
    }
}
