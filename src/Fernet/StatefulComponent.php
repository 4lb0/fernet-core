<?php

declare(strict_types=1);

namespace Fernet;

use stdClass;

trait StatefulComponent
{
    public bool $dirtyState = false;
    protected stdClass $state;
    private bool $persist = false;

    public function initState(bool $_persist = true, ...$params): self
    {
        $this->persist = $_persist;
        if ($this->persist && !session_id()) {
            session_start();
        }
        $this->state = $this->persist && isset($_SESSION[static::class]) ?
            (object) array_merge($params, (array) $_SESSION[static::class]) :
            (object) $params;
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

    public function addState(string $key, mixed $value): static
    {
        if (!isset($this->state->$key)) {
            $this->state->$key = [];
        }
        $this->state->$key[] = $value;
        $this->dirtyState = true;

        return $this;
    }


    public function removeState(string $key, int | string $positionOrKey): static
    {
        if (isset($this->state->$key)) {
            unset($this->state->$key[$positionOrKey]);
            $this->dirtyState = true;
        }

        return $this;
    }

    public function updateState(string $key, int | string $positionOrKey, mixed $value): static
    {
        if (isset($this->state->$key)) {
            $this->state->$key[$positionOrKey] = $value;
            $this->dirtyState = true;
        }

        return $this;
    }

    public function getState(): stdClass
    {
        return $this->state;
    }
}
