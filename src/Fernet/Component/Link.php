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
        [$component, $method] = explode('.', $this->to);
        if (!$method) {
            $method = 'route';
        }
        $link = $this->routes->get($component, $method, $this->params);
        $isActive = $this->request->server->get('REQUEST_URI') === $link;
        $css = '';
        if ($isActive || $this->css) {
            $classes = [$this->css, '__fm'];
            if ($isActive) {
                $classes[] = 'active';
            }
            $css = ' class="'.implode(' ', $classes).'"';
        }

        return "<a href=\"$link\"$css>$this->childContent</a>";
    }
}
