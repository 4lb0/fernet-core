<?php

use Fernet\Core\Events;
use Fernet\Core\Routes;
use Fernet\Framework;
use Fernet\Params;

function params(...$params): string
{
    return Params::component($params);
}

function onClick($callback, $unique = null): string
{
    return Events::onClick($callback, $unique);
}

function linkTo($component, $method = 'route', ...$params)
{
    $routes = Framework::getInstance()->getContainer()->get(Routes::class);

    return $routes->get($component, $method, $params);
}

function href($component, $method = 'route', $css = '', ...$params): string
{
    $container = Framework::getInstance()->getContainer();
    $routes = $container->get(Routes::class);
    $request = $container->get(Symfony\Component\HttpFoundation\Request::class);
    $link = $routes->get($component, $method, $params);
    $isActive = $request->server->get('REQUEST_URI') === $link;
    if ($isActive || $css) {
        $classes = [$css];
        if ($isActive) {
            $classes[] = 'active';
        }
        $css = ' class="'.implode(' ', $classes).'"';
    }

    return "href=\"$link\"$css";
}
