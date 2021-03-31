<?php

declare(strict_types=1);

namespace Fernet;

use Fernet\Component\Router;

trait Route
{
    public function route(): void
    {
        Framework::getInstance()->getContainer()->get(Router::class)->setRoute($this);
    }
}
