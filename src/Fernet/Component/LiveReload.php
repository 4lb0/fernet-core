<?php

declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Framework;

class LiveReload
{
    public bool $preventWrapper = true;
    private bool $devMode;

    public function __construct(Framework $framework)
    {
        $this->devMode = (bool) $framework->getConfig('devMode');
    }

    public function __toString(): string
    {
        if (!$this->devMode) {
            return '';
        }

        return <<<HTML
            <script>document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] +
':35729/livereload.js?snipver=1"></' + 'script>')</script>
HTML;
    }
}
