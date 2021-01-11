<?php

declare(strict_types=1);

namespace Fernet\Tests\Component;

class SimpleStaticTag
{
    public function __toString(): string
    {
        return '<p class="something">Simple static tag</p>';
    }
}
