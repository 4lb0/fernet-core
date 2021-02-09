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
    private ?Dispatcher $dispatcher = null;
    private array $routes = [];
    private string $configFile;

    /**
     * Routes constructor.
     * @param Framework $framework
     */
    public function __construct(Framework $framework)
    {
        $this->configFile = $framework->configFile('routingFile');
    }

    public function setDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @throws Exception
     */
    public function getDispatcher(): Dispatcher
    {
        if (!$this->dispatcher) {
            $this->setDispatcher($this->defaultDispatcher());
        }

        return $this->dispatcher;
    }

    /**
     * @throws Exception
     */
    public function defaultDispatcher(): Dispatcher
    {
        $routes = $this->getRoutes();

        return simpleDispatcher(function (RouteCollector $routeCollection) use ($routes) {
            foreach ($routes as $route => $handler) {
                $routeCollection->addRoute(['GET', 'POST'], $route, $handler);
            }
            $routeCollection->addRoute(['GET', 'POST'], self::DEFAULT_ROUTE, self::DEFAULT_ROUTE_NAME);
        });
    }

    /**
     * @throws Exception
     */
    public function getRoutes(): array
    {
        // TODO Add cache here
        if (!file_exists($this->configFile)) {
            return [];
        }

        try {
            $routes = json_decode(file_get_contents($this->configFile), true, 512, JSON_THROW_ON_ERROR);
            foreach ($routes as $route => $handler) {
                [$component, $method] = explode('.', $handler);
                $this->routes[$component][$method] = $route;
            }

            return $routes;
        } catch (JsonException $e) {
            throw new Exception("There was an error parsing the JSON in your routing file \"$this->configFile\": ".$e->getMessage());
        }
    }

    /**
     * @param string $component
     * @param string $method
     * @param array|null $args
     * @return string|null
     * @throws Exception
     */
    public function get(string $component, string $method, ?array $args = null): ?string
    {
        if (!$this->routes) {
            $this->defaultDispatcher();
        }
        if (isset($this->routes[$component][$method])) {
            $route = $this->routes[$component][$method];
            if ($args) {
                foreach ($args as $arg => $value) {
                    $route = str_replace('{'.$arg.'}', urlencode((string) $value), $route);
                }
            }

            return $route;
        }

        return null;
    }

    /**
     * @param Request $request
     * @return string|null
     * @throws Exception
     */
    public function dispatch(Request $request): ?string
    {
        $defaults = [Dispatcher::NOT_FOUND, null, []];
        [$routeFound, $handler, $vars] = $this->getDispatcher()->dispatch($request->getMethod(), $request->getPathInfo()) + $defaults;
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
