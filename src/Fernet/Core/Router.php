<?php

namespace Fernet\Core;

use Monolog\Logger;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router
{
    private Request $request;
    private Logger $log;
    private Routes $routes;

    public function __construct(Request $request, Logger $log, Routes $routes)
    {
        $this->request = $request;
        $this->log = $log;
        $this->routes = $routes;
    }

    /**
     * @param $defaultComponent
     *
     * @return Response
     * @throws Exception
     * @throws NotFoundException
     */
    public function route(Stringable | string $defaultComponent): Response
    {

        $response = false;
        $route = $this->routes->dispatch($this->request);
        if ($route) {
            [$class, $method] = explode('.', $route);
            $this->log->debug("Route matched $route");
            $component = new ComponentElement($class);
            $this->bind($component->getComponent());
            $response = $component->call($method, $this->getArgs());
        }
        if (!$response) {
            $response = new Response(
                (new ComponentElement($defaultComponent))->render(),
                Response::HTTP_OK
            );
        }

        return $response;
    }

    public function getArgs(): array
    {
        // TODO Change hardcoded string to constant or config
        $params = $this->request->query->get('fernet-params', []);
        $this->request->query->remove('fernet-params');
        $args = $this->request->query->all();
        foreach ($args as $key => $value) {
            if (strpos($key, '__fernet') !== false) {
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
        $args[] = $this->request;

        return array_values($args);
    }

    public function bind(Stringable $component): void
    {
        // TODO Change hardcoded string to constant or config
        foreach ($this->request->request->get('fernet-bind', []) as $key => $value) {
            $this->log->debug("Binding \"$key\" to", [$value]);
            $var = &$component;
            foreach (explode('.', $key) as $attr) {
                $var = &$var->$attr;
            }
            $var = $value;
        }
    }
}
