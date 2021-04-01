<?php

declare(strict_types=1);

namespace Fernet;

trait StatefulComponent
{
    public bool $dirtyState = false;
    protected $state;
    private bool $persist = false;

    public function initState(bool $_persist = true, ...$params): self
    {
        $this->persist = $_persist;
        if ($this->persist && !session_id()) {
            session_start();
        }
        $this->state = $this->persist && isset($_SESSION[static::class]) ? $_SESSION[static::class] : (object) $params;
        $this->dirtyState = false;
        if ($this->persist) {
            $_SESSION[static::class] = $this->state;
        }

        return $this;
    }

    public function setState(...$params): self
    {
        $this->state = (object) array_merge((array) $this->state, $params);
        $this->dirtyState = true;
        if ($this->persist) {
            $_SESSION[static::class] = $this->state;
        }

        return $this;
    }

    public function getState()
    {
        return $this->state;
    }
}
