<?php

declare(strict_types=1);

namespace Fernet\Tests\Component;

class TagWithEvent
{
    public bool $clicked = false;

    public function handleClick(): void
    {
        $this->clicked = true;
    }

    public function __toString(): string
    {
        return '<a @onClick="handleClick">Click me</a>';
    }
}
