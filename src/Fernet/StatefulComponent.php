<?php

declare(strict_types=1);

namespace Fernet;

trait StatefulComponent
{
    public bool $dirtyState = false;
    protected $state;
    private ?string $stateId;

    public function initState(?string $id, ...$params): self
    {
        if ($id) {
            session_start();
        }
        $this->stateId = $id;
        $this->state = $id && isset($_SESSION[$id]) ? $_SESSION[$id] : (object) $params;
        $this->dirtyState = false;
        if ($id) {
            $_SESSION[$id] = $this->state;
        }

        return $this;
    }

    public function setState(...$params): self
    {
        $this->state = (object) array_merge((array) $this->state, $params);
        $this->dirtyState = true;
        if ($this->stateId) {
            $_SESSION[$this->stateId] = $this->state;
        }

        return $this;
    }

    public function getState()
    {
        return $this->state;
    }
}
