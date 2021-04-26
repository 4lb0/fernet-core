<?php

declare(strict_types=1);

namespace Fernet;

use Fernet\Component\Error404;
use Fernet\Component\Error500;
use Symfony\Component\Yaml\Yaml;

/**
 * @property array plugins List of plugins installed
 * @property array routing Routes defined
 * @property string baseUri URL base path
 * @property array errorPages Error pages
 */
class Config
{
    public const DEFAULT_CONFIG_FILE = 'fernet.yml';

    private array $config = [
        'baseUri' => '/',
        'routing' => [],
        'plugins' => [],
        'errorPages' => [
            'error500' => Error500::class,
            'error404' => Error404::class,
        ],
    ];

    public function __construct(string $configFile = self::DEFAULT_CONFIG_FILE)
    {
        if (file_exists($configFile)) {
            $this->config = array_merge($this->config, Yaml::parseFile($configFile));
        }
    }

    public function get(string $name, mixed $default): mixed
    {
        $hasValue = false;
        $value = $this->config;
        foreach (explode('.', $name) as $var) {
            if (isset($value[$var])) {
                $value = $value[$var];
                $hasValue = true;
            }
        }

        return $hasValue ? $value : $default;
    }

    public function __get(string $name)
    {
        return $this->config[$name];
    }

    public function __set(string $name, mixed $value)
    {
        $this->config[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->config[$name]);
    }
}
