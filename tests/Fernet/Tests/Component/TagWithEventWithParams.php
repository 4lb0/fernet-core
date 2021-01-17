<?php

declare(strict_types=1);

namespace Fernet\Tests\Component;

use Fernet\Params;

class TagWithEventWithParams
{
    public object $user;

    public function handleClick(string $foo, object $user, int $number, bool $bool): void
    {
    }

    public function __toString(): string
    {
        return '<a @onClick="handleClick(' . Params::event("bar", $this->user, 25, false) . ')">Click me</a>';
    }
}
