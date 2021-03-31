<?php

declare(strict_types=1);

namespace Fernet;

trait UniqueComponent
{
    public function unique(): void
    {
        Framework::getInstance()->getContainer()->add(static::class, $this);
    }
}
