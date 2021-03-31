<?php

declare(strict_types=1);

namespace Fernet;

use Stringable;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class RedirectRoute implements Stringable
{
    private array $routeParams;

    abstract public function redirectTo(): string;

    public function route(...$params): RedirectResponse
    {
        $this->routeParams = $params;

        return new RedirectResponse($this->redirectTo());
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function __toString(): string
    {
        return '';
    }
}
