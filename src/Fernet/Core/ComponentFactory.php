<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Framework;
use League\Container\Container;
use Stringable;

class ComponentFactory
{
    private Container $container;
    private array $components = [];

    public function __construct(Framework $framework)
    {
        $this->container = $framework->getContainer();
    }

    public function add(Stringable $component): void
    {
        $this->components[$component::class] = $component;
    }

    public function exists(Stringable $component): bool
    {
        return isset($this->components[$component::class]);
    }

    private function get(string $class): Stringable
    {
        return $this->components[$class] ?? clone $this->container->get($class);
    }

    /**
     * @throws NotFoundException
     */
    public function create(string $name): Stringable
    {
        // TODO Add filesystem or memory cache to the string to object
        if (class_exists($name)) {
            return $this->get($name);
        }
        $namespaces = Framework::config('componentNamespaces');
        foreach ($namespaces as $namespace) {
            $classWithNamespace = $namespace.'\\'.$name;
            if (class_exists($classWithNamespace)) {
                return $this->get($classWithNamespace);
            }
        }
        throw new NotFoundException(sprintf('Component "%s" not defined in ["%s"]', $name, implode('", "', $namespaces)));
    }
}
