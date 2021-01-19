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
    private const DEFAULT_OPTIONS = [
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
    private const BOOTSTRAP_CLASS = 'Bootstrap';

    private Container $container;
    private Logger $log;
    private array $options;
    private array $events = [
        'onLoad' => [],
        'onRequest' => [],
        'onResponse' => [],
        'onError' => [],
    ];

    private function __construct(array $options)
    {
        $this->container = new Container();
        $this->container->delegate((new ReflectionContainer())->cacheResolutions());
        $this->container->add(self::class, $this);
        $this->options = $options;

        $logger = new Logger($options['logName']);
        $logger->pushHandler(new StreamHandler($options['logPath'], $options['logLevel']));
        $this->container->add(Logger::class, $logger);
        $this->log = $logger;
    }

    public static function setUp(array $options = [], $envPrefix = self::DEFAULT_ENV_PREFIX): self
    {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);
        foreach ($_ENV as $key => $value) {
            if (0 === strpos($key, $envPrefix)) {
                $key = substr($key, strlen($envPrefix));
                $key = Helper::camelCase($key);
                $options[$key] = is_bool($options[$key]) ?
                    filter_var($value, FILTER_VALIDATE_BOOLEAN) :
                    $value;
            }
        }
        self::$instance = new self($options);
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

    public function getConfig(string $option)
    {
        if (!isset($this->options[$option])) {
            $this->log->warning("Undefined config \"$option\"");

            return null;
        }

        return $this->options[$option];
    }

    public function setOption(string $option, $value): self
    {
        $this->options[$option] = $value;

        return $this;
    }

    public function addOption(string $option, $value): self
    {
        $this->options[$option][] = $value;

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
        $filepath = self::configFile('pluginFile');
        if (file_exists($filepath)) {
            try {
                $plugins = json_decode(file_get_contents($filepath), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($plugins)) {
                    throw new Core\Exception("Plugin file \"$filepath\" should contain an array");
                }
                foreach ($plugins as $pluginName) {
                    $class = "\\$pluginName\\".self::BOOTSTRAP_CLASS;
                    if (class_exists($class) && is_subclass_of($class, PluginBootstrap::class)) {
                        $this->getLog()->debug("Load plugin $pluginName");
                        $plugin = new $class();
                        $plugin->setUp($this);
                    }
                }
            } catch (JsonException $e) {
                throw new Core\Exception("Plugin file \"$filepath\" is not a valid JSON");
            }
        }
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
