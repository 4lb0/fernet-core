<?php

namespace Fernet\Core;

use Fernet\Component\Router as ComponentRouter;
use Monolog\Logger;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router
{
    public function __construct(
        private Logger $log,
        private Routes $routes,
        private ?Events $events = null,
        private ?ComponentRouter $componentRouter = null,
        private ?JsBridge $jsBridge = null
    ) {
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
            [$class, $method] = explode('.', $route.'.');
            if (!$method) {
                $method = 'route';
            }
            $this->log->debug("Route matched $route");
            $component = new ComponentElement($class);
            if ($request->headers->has('X-Fernet-Js')) {
                $this->log->debug("PUT $route");
                $this->jsBridge->setRoute($component->getComponent());
            }
            $this->bind($component->getComponent(), $request);
            if (!empty($this->componentRouter)) {
                $this->componentRouter->setRoute($component->getComponent());
            }
            $response = $component->call($method, $this->getArgs($request));
        }
        if (!$response) {
            $this->log->debug('No response, rendering main component');
            $content = (new ComponentElement($defaultComponent))->setMain()->render();
            if ($this->events) {
                $content = $this->events->replaceCallbacks($content);
            }
            $response = new Response($content, Response::HTTP_OK);
        }

        return $response;
    }

    public function getArgs(Request $request): array
    {
        // TODO Change hardcoded string to constant or config
        $args = $request->query->all();
        $params = $args['fernet-params'] ?? [];
        unset($args['fernet-params']);
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
        foreach ($request->request->all()['fernet-bind'] ?? [] as $key => $value) {
            $this->log->debug("Binding \"$key\" to", [$value]);
            $var = &$component;
            foreach (explode('.', $key) as $attr) {
                $var = &$var->$attr;
            }
            $var = $value;
        }
    }
}
