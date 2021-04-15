<?php

declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Core\Routes;
use Symfony\Component\HttpFoundation\Request;

class Link
{
    public string $to;
    public array $params = [];
    public string $css = '';
    public string $childContent;

    public function __construct(private Routes $routes, private Request $request)
    {
    }

    /**
     * @throws \Fernet\Core\Exception
     */
    public function __toString(): string
    {
        [$component, $method] = explode('.', $this->to.'.');
        if (!$method) {
            $method = 'route';
        }
        $link = $this->routes->get($component, $method, $this->params);
        $isActive = $this->request->server->get('REQUEST_URI') === $link;
        $classes = ['__fl'];
        if ($this->css) {
            $classes[] = $this->css;
        }
        if ($isActive) {
            $classes[] = 'active';
        }
        $css = implode(' ', $classes);

        return "<a href=\"$link\" class=\"$css\">$this->childContent</a>";
    }
}
