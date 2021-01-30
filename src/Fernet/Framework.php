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
use Fernet\Core\PluginBootstrap;
use Fernet\Core\Router;
use JsonException;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Framework
{
    private const DEFAULT_CONFIG = [
        'devMode' => false,
        'enableJs' => true,
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
    ];

    private static self $instance;

    /**
     * Prefix used in env file.
     */
    private const DEFAULT_ENV_PREFIX = 'FERNET_';
    private const PLUGIN_FILE = 'plugin.php';

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
            if (0 === strpos($key, $envPrefix)) {
                $key = substr($key, strlen($envPrefix));
                $key = Helper::camelCase($key);
                $configs[$key] = is_bool($configs[$key]) ?
                    filter_var($value, FILTER_VALIDATE_BOOLEAN) :
                    $value;
            }
        }
        self::$instance = new self($configs);
        try {
            self::$instance->loadPlugins();
        } catch (Throwable $error) {
            self::$instance->getLog()->error($error->getMessage());
            $response = new Response(
                self::$instance->showError($error),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
            $response->send();
            exit;
        }

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

    public static function configFile(string $name): string
    {
        $framework = self::getInstance();

        return $framework->getConfig('rootPath').DIRECTORY_SEPARATOR.$framework->getConfig($name);
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

    public function setConfig(string $config, $value): self
    {
        $this->configs[$config] = $value;

        return $this;
    }

    public function addConfig(string $config, $value): self
    {
        $this->configs[$config][] = $value;

        return $this;
    }

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

    /**
     * @throws Core\Exception
     */
    public function loadPlugins(): void
    {
        $plugins = $this->warmUpPlugins();
        // TODO: Cache warm up
        foreach ($plugins as $pluginName => $class) {
            $this->getLog()->debug("Load plugin $pluginName");
            (new $class())->setUp($this);
        }
    }

    /**
     * @throws Core\Exception
     */
    public function warmUpPlugins(): array
    {
        $filepath = self::configFile('pluginFile');
        if (!file_exists($filepath)) {
            return [];
        }
        $plugins = [];
        try {
            $list = json_decode(file_get_contents($filepath), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($plugins)) {
                throw new Core\Exception("Plugin file \"$filepath\" should contain an array");
            }
            foreach ($list as $pluginName) {
                $file = $this->getConfig('rootPath')."/vendor/$pluginName/".self::PLUGIN_FILE;
                if (!file_exists($file)) {
                    throw new Core\Exception("Plugin \"$pluginName\" is not a valid plugin");
                }
                $class = require $file;
                if (class_exists($class) && is_subclass_of($class, PluginBootstrap::class)) {
                    $this->getLog()->debug("Warm up plugin $pluginName");
                    $plugins[$pluginName] = $class;
                    // TODO: When I should run the install?
                    $this->getLog()->debug("Install plugin $pluginName");
                    (new $class())->install($this);
                } else {
                    throw new Core\Exception("Plugin \"$pluginName\" Bootstrap class should extend ".PluginBootstrap::class);
                }
            }
        } catch (JsonException $e) {
            throw new Core\Exception("Plugin file \"$filepath\" is not a valid JSON");
        }

        return $plugins;
    }

    public function run($component, ?Request $request = null): Response
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
            $response = $router->route($component);
            $this->dispatch('onResponse', [$response]);
        } catch (NotFoundException $notFoundException) {
            $this->log->notice('Route not found');

            return new Response(
                $this->showError($notFoundException, 'error404'),
                Response::HTTP_NOT_FOUND
            );
        } catch (Exception $exception) {
            $this->log->error($exception->getMessage());
            $response = new Response(
                $this->showError($exception),
                Response::HTTP_INTERNAL_SERVER_ERROR
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

    public function getLog(): Logger
    {
        return $this->log;
    }
}
