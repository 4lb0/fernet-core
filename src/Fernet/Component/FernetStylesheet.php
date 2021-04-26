<?php

declare(strict_types=1);

namespace Fernet\Component;
use Fernet\UniqueComponent;

class FernetStylesheet
{
    use UniqueComponent;

    public function __construct()
    {
        $this->unique();
    }

    private array $styles = [];

    public function add(string $css, ?string $id = null): void
    {
        if (!$id) {
            $this->styles[] = $css;
        } else {
            $this->styles[$id] = $css;
        }
    }

    public function __toString(): string
    {
        return '<style>'.onReady(fn () => implode("\n", $this->styles)).'</style>';
    }
}
