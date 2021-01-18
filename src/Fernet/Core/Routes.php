<?php

declare(strict_types=1);

namespace Fernet\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Fernet\Framework;
use JsonException;
use Symfony\Component\HttpFoundation\Request;

class Routes
{
    private const DEFAULT_ROUTE = '/{component}/{method}';
    private const DEFAULT_ROUTE_NAME = '__default_fernet_route';
    private Dispatcher $dispatcher;
    private array $routes = [];

    /**
     * Routes constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $routes = $this->getRoutes();
        $this->dispatcher = simpleDispatcher(function (RouteCollector $routeCollection) use ($routes) {
            foreach ($routes as $route => $handler) {
                $routeCollection->addRoute(['GET', 'POST'], $route, $handler);
            }
            $routeCollection->addRoute(['GET', 'POST'], self::DEFAULT_ROUTE, self::DEFAULT_ROUTE_NAME);
        });
    }

    /**
     * @throws Exception
     */
    private function getRoutes(): array
    {
        $filename = Framework::config('rootPath').DIRECTORY_SEPARATOR.Framework::config('routingFile');
        if (!file_exists($filename)) {
            return [];
        }

        try {
            $routes = json_decode(file_get_contents($filename), true, 512, JSON_THROW_ON_ERROR);
            foreach ($routes as $route => $handler) {
                [$component, $method] = explode('.', $handler);
                $this->routes[$component][$method] = $route;
            }

            return $routes;
        } catch (JsonException $e) {
            throw new Exception("There was an error parsing the JSON in your routing file \"$filename\": ".$e->getMessage());
        }
    }

    public function get(string $component, string $method, ?array $args = null): ?string
    {
        if (isset($this->routes[$component][$method])) {
            $route = $this->routes[$component][$method];
            if ($args) {
                foreach ($args as $arg => $value) {
                    $route = str_replace('{'.$arg.'}', $value, $route);
                }
            }

            return $route;
        }

        return null;
    }

    public function dispatch(Request $request): ?string
    {
        $defaults = [Dispatcher::NOT_FOUND, null, []];
        [$routeFound, $handler, $vars] = $this->dispatcher->dispatch($request->getMethod(), $request->getPathInfo()) + $defaults;
        if (Dispatcher::FOUND !== $routeFound) {
            return null;
        }
        if (self::DEFAULT_ROUTE_NAME === $handler) {
            $handler = Helper::pascalCase($vars['component']).'.'.Helper::camelCase($vars['method']);
        } else {
            $request->query->add($vars);
        }

        return $handler;
    }
}
