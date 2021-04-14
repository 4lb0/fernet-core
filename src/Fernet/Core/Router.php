<?php

namespace Fernet\Core;

use Monolog\Logger;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router
{
    private Logger $log;
    private Routes $routes;

    public function __construct(Logger $log, Routes $routes)
    {
        $this->log = $log;
        $this->routes = $routes;
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    public function route(Stringable | string $defaultComponent, Request $request): Response
    {
        $response = false;
        $this->log->debug('Request '.$request->getMethod().' '.$request->getUri());
        $route = $this->routes->dispatch($request);
        if ($route) {
            [$class, $method] = explode('.', $route);
            $this->log->debug("Route matched $route");
            $component = new ComponentElement($class);
            $this->bind($component->getComponent(), $request);
            $response = $component->call($method, $this->getArgs($request));
        }
        if (!$response) {
            $this->log->debug('No response, rendering main component');
            $response = new Response(
                (new ComponentElement($defaultComponent))->setMain()->render(),
                Response::HTTP_OK
            );
        }

        return $response;
    }

    public function getArgs(Request $request): array
    {
        // TODO Change hardcoded string to constant or config
        $params = $request->query->get('fernet-params', []);
        $request->query->remove('fernet-params');
        $args = $request->query->all();
        foreach ($args as $key => $value) {
            if (str_contains($key, '__fernet')) {
                unset($args[$key]);
            }
        }
        foreach ($params as $param) {
            // FIXME This is completely unsafe, refactor asap
            $value = @unserialize($param, ['allowed_classes' => true]);
            if (false === $value && $param !== serialize(false)) {
                $this->log->error('Error when trying to unserialize param', [$param]);
                $args[] = null;
            } else {
                $args[] = $value;
            }
        }
        $this->log->debug('Arguments passed to component event', [$args]);
        $args[] = $request;

        return array_values($args);
    }

    public function bind(Stringable $component, Request $request): void
    {
        // TODO Change hardcoded string to constant or config
        foreach ($request->request->get('fernet-bind', []) as $key => $value) {
            $this->log->debug("Binding \"$key\" to", [$value]);
            $var = &$component;
            foreach (explode('.', $key) as $attr) {
                $var = &$var->$attr;
            }
            $var = $value;
        }
    }
}
