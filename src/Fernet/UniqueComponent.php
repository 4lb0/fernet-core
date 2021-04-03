<?php

declare(strict_types=1);

namespace Fernet;

use Fernet\Core\ComponentFactory;

trait UniqueComponent
{
    public function unique(): void
    {
        Framework::getInstance()->getContainer()->get(ComponentFactory::class)->add($this);
    }
}
