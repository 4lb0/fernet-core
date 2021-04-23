<?php

declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Core\Exception;
use Fernet\Core\Routes;
use Symfony\Component\HttpFoundation\Request;

class Link
{
    public string $to;
    public array $params = [];
    public string $class = '';
    public string $activeClass = 'active';
    public string $childContent;
    public bool $preventWrapper = true;

    public function __construct(private Routes $routes, private Request $request)
    {
    }

    /**
     * @throws Exception
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
        if ($this->class) {
            $classes[] = $this->class;
        }
        if ($isActive) {
            $classes[] = $this->activeClass;
        }
        $css = implode(' ', $classes);

        return "<a href=\"$link\" class=\"$css\">$this->childContent</a>";
    }
}
