<?php

declare(strict_types=1);

namespace Fernet\Component;

class FernetFavicon
{
    public function __toString(): string
    {
        $svg = (string) new FernetLogo();
        $svg = trim(rawurlencode($svg));

        return <<<EOHTML
            <link rel="icon" href='data:image/svg+xml,$svg'>
EOHTML;
    }
}
