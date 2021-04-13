<?php

declare(strict_types=1);

namespace Fernet;

use Exception;
use Fernet\Component\Error404;
use Fernet\Component\Error500;
use Fernet\Component\FernetShowError;
use Fernet\Core\ComponentElement;
use Fernet\Core\Helper;
use Fernet\Core\NotFoundException;
use Fernet\Core\PluginLoader;
use Fernet\Core\Router;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Framework
{
    private const DEFAULT_CONFIG = [
        'devMode' => false,
        'urlPrefix' => '/',
        'componentNamespaces' => [
            'App\\Component',
            'Fernet\\Component',
        ],
        'logPath' => 'php://stdout',
        'logName' => 'fernet',
        'logLevel' => Logger::INFO,
        'error404' => Error404::class,
        'error500' => Error500::class,
        'rootPath' => '.',
        'routingFile' => 'routing.json',
        'pluginFile' => 'plugins.json',
        'enableJs' => false,
    ];

    private static self $instance;

    /**
     * Prefix used in env file.
     */
    private const DEFAULT_ENV_PREFIX = 'FERNET_';

    private Container $container;
    private Logger $log;
    private array $configs;
    private array $events = [
        'onLoad' => [],
        'onRequest' => [],
        'onResponse' => [],
        'onError' => [],
    ];

    private function __construct(array $configs)
    {
        $this->container = new Container();
        $this->container->delegate((new ReflectionContainer())->cacheResolutions());
        $this->container->add(self::class, $this);
        $this->configs = $configs;

        $logger = new Logger($configs['logName']);
        $logger->pushHandler(new StreamHandler($configs['logPath'], $configs['logLevel']));
        $this->container->add(Logger::class, $logger);
        $this->log = $logger;
    }

    public static function setUp(array $configs = [], $envPrefix = self::DEFAULT_ENV_PREFIX): self
    {
        $configs = array_merge(self::DEFAULT_CONFIG, $configs);
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, $envPrefix)) {
                $key = substr($key, strlen($envPrefix));
                $key = Helper::camelCase($key);
                $configs[$key] = is_bool($configs[$key]) ?
                    filter_var($value, FILTER_VALIDATE_BOOLEAN) :
                    $value;
            }
        }
        self::$instance = new self($configs);
        self::$instance->getContainer()->get(PluginLoader::class)->loadPlugins();

        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::setUp();
        }

        return self::$instance;
    }

    public static function config(string $name)
    {
        return self::getInstance()->getConfig($name);
    }

    public function configFile(string $name): string
    {
        return $this->getConfig('rootPath').DIRECTORY_SEPARATOR.$this->getConfig($name);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getConfig(string $config)
    {
        if (!isset($this->configs[$config])) {
            $this->log->warning("Undefined config \"$config\"");

            return null;
        }

        return $this->configs[$config];
    }

    /**
     * @Framework
     *
     * @param string $config Config name
     * @param mixed  $value  Config value
     *
     * @return Framework
     */
    public function setConfig(string $config, mixed $value): self
    {
        $this->configs[$config] = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function addConfig(string $config, $value): self
    {
        $this->configs[$config][] = $value;

        return $this;
    }

    /**
     * @Framework
     *
     * @param string   $event    Event to be subscribed
     * @param callable $callback Callback
     *
     * @return Framework
     */
    public function subscribe(string $event, callable $callback): self
    {
        $this->events[$event][] = $callback;

        return $this;
    }

    public function dispatch(string $event, array $args = []): void
    {
        foreach ($this->events[$event] as $position => $callback) {
            $this->log->debug("Dispatch \"$event\" callback #$position");
            call_user_func_array($callback, $args);
        }
    }

    public function run(Stringable | string $component, ?Request $request = null): Response
    {
        try {
            $this->dispatch('onLoad', [$this]);
            if (!$request) {
                $request = Request::createFromGlobals();
            }
            $this->dispatch('onRequest', [$request]);
            $this->container->add(Request::class, $request);
            /** @var Router $router */
            $router = $this->container->get(Router::class);
            $response = $router->route($component, $request);
            $this->dispatch('onResponse', [$response]);
        } catch (NotFoundException $notFoundException) {
            $this->log->notice('Route not found');

            return new Response(
                $this->showError($notFoundException, 'error404'),
                Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $error) {
            $this->log->error($error->getMessage());
            $response = new Response(
                $this->showError($error),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $response->prepare($request);

        return $response;
    }

    public function showError(Throwable $error, string $type = 'error500'): string
    {
        $this->dispatch('onError', [$error]);
        try {
            $element = $this->getConfig('devMode') ?
                new ComponentElement(FernetShowError::class, ['error' => $error]) :
                new ComponentElement($this->getConfig($type));

            return $element->render();
        } catch (Exception $e) {
            $this->log->error('Error when trying to show the error', [$e]);

            return 'Error: '.$error->getMessage();
        }
    }
}
