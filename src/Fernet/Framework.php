<?php

declare(strict_types=1);

namespace Fernet;

use Fernet\Core\CaseConverter;
use Fernet\Core\PluginLoader;
use Fernet\Core\Run;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Framework
{
    public const URL = 'https://fernet.ws';

    private const DEFAULT_CONFIG = [
        'enableJs' => false,
        'resourcesPath' => null,
        'componentNamespaces' => [
            'App\\Component',
            'Fernet\\Component',
        ],
        'rootPath' => '.',
        // env
        'devMode' => false,
        'logPath' => 'php://stdout',
        'logName' => 'fernet',
        'logLevel' => Logger::INFO,
        'editor' => 'sublime',
    ];

    private static self $instance;

    /**
     * Prefix used in env file.
     */
    private const DEFAULT_ENV_PREFIX = 'FERNET_';

    private Container $container;
    private Logger $log;
    private array $configs;

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

    public static function setUp(array $configs = []): self
    {
        $configs = array_merge(self::DEFAULT_CONFIG, $configs);
        $configs['resourcesPath'] = dirname(__DIR__, 2).'/resources/';
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, self::DEFAULT_ENV_PREFIX)) {
                $key = substr($key, strlen(self::DEFAULT_ENV_PREFIX));
                $key = CaseConverter::camelCase($key);
                $configs[$key] = is_bool($configs[$key]) ?
                    filter_var($value, FILTER_VALIDATE_BOOLEAN) :
                    $value;
            }
        }
        self::$instance = new self($configs);
        $pluginLoader = self::$instance->getContainer()->get(PluginLoader::class);
        if (getenv('FERNET_SETUP_PLUGINS')) {
            $pluginLoader->install();
        }
        $pluginLoader->load();

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

    public function addConfig(string $config, mixed $value): Framework
    {
        $this->configs[$config][] = $value;

        return $this;
    }

    public function run(Stringable | string $component, ?Request $request = null): Response
    {
        if (!$request) {
            $request = Request::createFromGlobals();
        }
        $this->container->add(Request::class, $request);

        return $this->container->get(Run::class)->__invoke($component, $request);
    }
}
