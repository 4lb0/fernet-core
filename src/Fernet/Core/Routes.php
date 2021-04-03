<?php

declare(strict_types=1);

namespace Fernet\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Fernet\Framework;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

class Routes
{
    private const DEFAULT_ROUTE = '/{component}/{method}';
    private const DEFAULT_ROUTE_NAME = '__default_fernet_route';
    private ?Dispatcher $dispatcher = null;
    private array $routes = [];
    private string $configFile;
    private Logger $log;

    /**
     * Routes constructor.
     */
    public function __construct(Framework $framework, Logger $logger)
    {
        $this->configFile = $framework->configFile('routingFile');
        $this->log = $logger;
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
        $this->log->debug('Using default routing dispatcher');
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
            $this->log->debug('No routing file');

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
            $message = "Error parsing the JSON in your routing file \"$this->configFile\": ".$e->getMessage();
            $this->log->error($message);
            throw new Exception($message);
        }
    }

    /**
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
