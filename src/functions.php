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
    $events = Framework::getInstance()->getContainer()->get(Events::class);
    return $events->onClick($callback, $unique);
}

function onReady($callback): string
{
    $events = Framework::getInstance()->getContainer()->get(Events::class);
    return $events->onReady($callback);
}

function linkTo($component, $method = 'route', ...$params)
{
    $routes = Framework::getInstance()->getContainer()->get(Routes::class);

    return $routes->get($component, $method, $params);
}
