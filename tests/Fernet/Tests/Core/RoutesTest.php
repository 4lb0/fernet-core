<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Config;
use Fernet\Core\Routes;
use Fernet\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RoutesTest extends TestCase
{
    private function createRoutes(): Routes
    {
        $config = new Config();
        $config->routing = [
            '/about' => 'Menu.handleAbout',
            '/some/foo/bar/page' => 'FooBar.renderPage',
            '/name/{name}/age/{age}' => 'UserProfile.show',
        ];

        return new Routes($this->createNullLogger(), $config);
    }

    public function testLink(): void
    {
        $routes = $this->createRoutes();
        self::assertEquals('/about', $routes->get('Menu', 'handleAbout'));
        self::assertEquals('/some/foo/bar/page', $routes->get('FooBar', 'renderPage'));
        self::assertEquals('/not-mapped-component/handle-click', $routes->get('NotMappedComponent', 'handleClick'));
        self::assertEquals('/name/John+Doe/age/75', $routes->get('UserProfile', 'show', ['name' => 'John Doe', 'age' => 75]));
    }

    public function testDispatch(): void
    {
        $routes = $this->createRoutes();
        $request = $this->createRequest('/about');
        self::assertEquals('Menu.handleAbout', $routes->dispatch($request));

        $request = $this->createRequest('/some/foo/bar/page');
        self::assertEquals('FooBar.renderPage', $routes->dispatch($request));

        $request = new Request();
        self::assertEquals(null, $routes->dispatch($request));
    }

    public function testDefaultHandlersRoutes(): void
    {
        $routes = new Routes($this->createNullLogger(), new Config());
        $request = $this->createRequest('/hello-component/some-handler');
        self::assertEquals('HelloComponent.someHandler', $routes->dispatch($request));
    }
}
