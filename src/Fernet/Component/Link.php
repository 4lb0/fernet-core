<?php

declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Core\Routes;
use Symfony\Component\HttpFoundation\Request;

class Link
{
    public const CSS_CLASS = '__fl';
    public string $to;
    public array $params = [];
    public string $class = '';
    public string $activeClass = 'active';
    public string $childContent;
    public bool $preventWrapper = true;

    public function __construct(private Routes $routes, private Request $request)
    {
    }

    public function __toString(): string
    {
        [$component, $method] = explode('.', $this->to.'.');
        if (!$method) {
            $method = 'route';
        }
        $link = $this->routes->get($component, $method, $this->params);
        $isActive = $this->request->server->get('REQUEST_URI') === $link;
        $classes = [static::CSS_CLASS];
        if ($this->class) {
            $classes[] = $this->class;
        }
        if ($isActive) {
            $classes[] = $this->activeClass;
        }
        $css = implode(' ', $classes);

        return "<a href=\"$link\" class=\"$css\" data-active-class=\"$this->activeClass\">$this->childContent</a>";
    }
}
