<?php

declare(strict_types=1);

namespace Fernet\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Fernet\Config;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

class Routes
{
    private const DEFAULT_ROUTE = '/{component}[/{method}]';
    private const DEFAULT_ROUTE_NAME = '__default_fernet_route';
    private const DEFAULT_METHOD = 'route';
    private Dispatcher $dispatcher;
    private array $routes = [];

    /**
     * Routes constructor.
     */
    public function __construct(private Logger $log, private Config $config)
    {
    }

    public function setDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function getDispatcher(): Dispatcher
    {
        if (empty($this->dispatcher)) {
            $this->setDispatcher($this->defaultDispatcher());
        }

        return $this->dispatcher;
    }

    public function defaultDispatcher(): Dispatcher
    {
        $this->log->debug('Using default routing dispatcher');
        $routes = $this->getRoutes();

        return simpleDispatcher(function (RouteCollector $routeCollection) use ($routes) {
            foreach ($routes as $route => $handler) {
                $routeCollection->addRoute(['GET', 'POST', 'PUT'], $route, $handler);
            }
            $routeCollection->addRoute(['GET', 'POST', 'PUT'], self::DEFAULT_ROUTE, self::DEFAULT_ROUTE_NAME);
        });
    }

    public function getRoutes(): array
    {
        $routes = $this->config->routing;
        foreach ($routes as $route => $handler) {
            [$component, $method] = explode('.', $handler.'.');
            if (!$method) {
                $method = static::DEFAULT_METHOD;
            }
            $this->routes[$component][$method] = $route;
        }

        return $routes;
    }

    public function get(string $component, string $method, ?array $args = null): string
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

        $url = $this->config->baseUri.CaseConverter::kebab($component);
        if ('route' !== $method) {
            $url .= '/'.CaseConverter::kebab($method);
        }
        if ($args) {
            $param = [];
            foreach ($args as $arg) {
                $param[] = serialize($arg);
            }
            $url .= '?'.htmlentities(http_build_query(['fernet-params' => $param]));
        }

        return $url;
    }

    public function dispatch(Request $request): ?string
    {
        $defaults = [Dispatcher::NOT_FOUND, null, []];
        [$routeFound, $handler, $vars] = $this->getDispatcher()->dispatch($request->getMethod(), $request->getPathInfo()) + $defaults;
        if (Dispatcher::FOUND !== $routeFound) {
            return null;
        }
        if (self::DEFAULT_ROUTE_NAME === $handler) {
            if (!isset($vars['method'])) {
                $vars['method'] = 'route';
            }
            $handler = CaseConverter::pascalCase($vars['component']).'.'.CaseConverter::camelCase($vars['method']);
        } else {
            $request->query->add($vars);
        }

        return $handler;
    }
}
