<?php

declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Core\ComponentElement;
use Fernet\Framework;
use Stringable;

class Router
{
    public const ROUTER_ID = '__fr';

    public string $default = 'Error404';

    private string | Stringable $route;

    public function __construct(private Framework $framework)
    {
        $framework->getContainer()->add(self::class, $this);
    }

    public function setRoute(string | Stringable $component): void
    {
        $this->route = $component;
    }

    public function __toString(): string
    {
        $content = (new ComponentElement($this->route ?? $this->default))->render();

        if ($this->framework->getConfig('enableJs')) {
            $wrapperClass = ComponentElement::WRAPPER_CLASS;
            $routerId = static::ROUTER_ID;

            return "<span id=\"$routerId\" class=\"$wrapperClass\">$content</span>";
        }

        return $content;
    }
}
