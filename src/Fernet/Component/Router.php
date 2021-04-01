<?php

declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Core\ComponentElement;
use Fernet\Core\NotFoundException;
use Fernet\Framework;
use Stringable;

class Router
{
    public string $default = 'Error404';

    private string | Stringable $route;

    public function __construct(Framework $framework)
    {
        $framework->getContainer()->add(self::class, $this);
    }

    public function setRoute(string | Stringable $component): void
    {
        $this->route = $component;
    }

    /**
     * @throws NotFoundException
     */
    public function __toString(): string
    {
        $component = new ComponentElement($this->route ?? $this->default);

        return $component->render();
    }
}
