<?php

declare(strict_types=1);

namespace Fernet\Core;

use function call_user_func_array;
use Fernet\Framework;
use function get_class;
use function is_string;
use Monolog\Logger;

class ComponentElement
{
    private const WRAPPER = '<div id="_fernet_component_%d" class="_fernet_component">%s</div>';
    private object $component;

    private static int $idCounter = 0;

    /**
     * ComponentElement constructor.
     *
     * @param mixed  $classOrObject Object or the name of the component
     * @param array  $params        The params the object need to be created
     * @param string $childContent  The HTML child content if applied
     *
     * @throws Exception
     * @throws NotFoundException
     */
    public function __construct($classOrObject, array $params = [], string $childContent = '')
    {
        $component = is_string($classOrObject) ?
            $this->getObject($classOrObject) :
            $classOrObject;
        if (!method_exists($component, '__toString')) {
            $class = get_class($component);
            throw new Exception("Component \"$class\" needs to implement __toString method");
        }
        foreach ($params as $key => $value) {
            $component->$key = $value;
        }
        $component->childContent = $childContent;
        $this->component = $component;
    }

    public function getComponent(): object
    {
        return $this->component;
    }

    private function getFromContainer(string $class): object
    {
        return Framework::getInstance()->getContainer()->get($class);
    }

    /**
     * @param string $class Tag name of the component to search in the namespaces
     *
     * @return object
     * @throws NotFoundException
     */
    private function getObject(string $class): object
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
     * @return mixed
     *
     * @throws NotFoundException
     */
    public function call($method, $args)
    {
        if (!method_exists($this->component, $method)) {
            throw new NotFoundException(sprintf('Method "%s" not found in component "%s"', $method, get_class($this->component)));
        }

        return call_user_func_array([$this->component, $method], $args);
    }

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    public function render(): string
    {
        $class = get_class($this->component);
        $this->getFromContainer(Logger::class)->debug("Rendering \"$class\"");
        $content = (string) $this->component;
        $content = $this->getFromContainer(ReplaceComponents::class)->replace($content);
        $content = $this->getFromContainer(ReplaceAttributes::class)->replace($content, $this->component);
        if (
            (isset($this->component->preventWrapper) && $this->component->preventWrapper)
            || !Framework::config('enableJs')
            ) {
            return $content;
        }
        $id = static::$idCounter++;

        return sprintf(static::WRAPPER, $id, $content);
    }
}
