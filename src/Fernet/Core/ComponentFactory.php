<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Framework;
use Fernet\UniqueComponent;
use League\Container\Container;
use Stringable;

class ComponentFactory
{
    private Container $container;
    private array $components = [];
    private array $componentNamespaces;

    public function __construct(Framework $framework)
    {
        $this->container = $framework->getContainer();
        $this->componentNamespaces = (array) $framework->getConfig('componentNamespaces');
    }

    public function add(Stringable $component): void
    {
        $this->container->add($component::class, $component);
        $this->components[$component::class] = $component;
    }

    public function exists(Stringable $component): bool
    {
        return isset($this->components[$component::class]);
    }

    private function get(string $class): Stringable
    {
        if (isset($this->components[$class])) {
            return $this->components[$class];
        }
        $component = $this->container->get($class);

        return in_array(UniqueComponent::class, class_uses($component), true) ? $component : clone $component;
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
        foreach ($this->componentNamespaces as $namespace) {
            $classWithNamespace = $namespace.'\\'.$name;
            if (class_exists($classWithNamespace)) {
                return $this->get($classWithNamespace);
            }
        }
        throw new NotFoundException(sprintf('Component "%s" not defined in ["%s"]', $name, implode('", "', $this->componentNamespaces)));
    }
}
