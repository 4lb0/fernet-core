<?php

declare(strict_types=1);

namespace Fernet\Core;

use function call_user_func_array;
use Fernet\Framework;
use function get_class;
use function is_string;
use Monolog\Logger;
use Stringable;

class ComponentElement
{
    private Stringable $component;

    /**
     * ComponentElement constructor.
     *
     * @param mixed  $classOrObject Object or the name of the component
     * @param array  $params        The params the object need to be created
     * @param string $childContent  The HTML child content if applied
     *
     * @throws NotFoundException
     */
    public function __construct(Stringable | string $classOrObject, array $params = [], string $childContent = '')
    {
        $component = is_string($classOrObject) ?
            $this->getObject($classOrObject) :
            $classOrObject;
        foreach ($params as $key => $value) {
            $component->$key = $value;
        }
        $component->childContent = $childContent;
        $this->component = $component;
    }

    public function getComponent(): Stringable
    {
        return $this->component;
    }

    private function getFromContainer(string $class): object
    {
        return Framework::getInstance()->getContainer()->get($class);
    }

    /**
     * @throws NotFoundException
     */
    private function getObject(string $class): Stringable
    {
        // TODO Add filesystem or memory cache to the string to object
        if (class_exists($class)) {
            return $this->getFromContainer($class);
        }
        $namespaces = Framework::config('componentNamespaces');
        foreach ($namespaces as $namespace) {
            $classWithNamespace = $namespace.'\\'.$class;
            if (class_exists($classWithNamespace)) {
                return $this->getFromContainer($classWithNamespace);
            }
        }
        throw new NotFoundException(sprintf('Component "%s" not defined in ["%s"]', $class, implode('", "', $namespaces)));
    }

    /**
     * @param $method
     * @param $args
     *
     * @throws NotFoundException
     */
    public function call($method, $args): mixed
    {
        if (!method_exists($this->component, $method)) {
            throw new NotFoundException(sprintf('Method "%s" not found in component "%s"', $method, get_class($this->component)));
        }

        return call_user_func_array([$this->component, $method], $args);
    }

    private function _render(): string
    {
        $content = (string) $this->component;
        $content = $this->getFromContainer(ReplaceComponents::class)->replace($content);
        return $this->getFromContainer(ReplaceAttributes::class)->replace($content, $this->component);
    }

    public function render(): string
    {
        $class = get_class($this->component);
        $log = $this->getFromContainer(Logger::class);
        $log->debug("Start rendering \"$class\"");
        $lastEvent = Events::getLastEvent();
        $content = $this->_render();
        $log->debug("Finish rendering \"$class\"");
        if (isset($this->component->dirtyState) && $this->component->dirtyState) {
            $log->debug("Start rendering again because state is dirty \"$class\"");
            Events::restore($lastEvent);
            // Restore the events because we're going to recreate them
            $content = $this->_render();
            $log->debug("Finish rendering dirty \"$class\"");
        }

        return $content;
    }
}
