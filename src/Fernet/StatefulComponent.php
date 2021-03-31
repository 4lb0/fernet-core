<?php

declare(strict_types=1);

namespace Fernet;

trait StatefulComponent
{
    public bool $dirtyState = false;
    protected object $state;

    public function initState(...$params): self
    {
        $this->state = (object) $params;
        $this->dirtyState = false;

        return $this;
    }

    public function setState(...$params): self
    {
        $this->state = (object) array_merge((array) $this->state, $params);
        $this->dirtyState = true;

        return $this;
    }
}
