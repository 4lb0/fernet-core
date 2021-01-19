<?php

declare(strict_types=1);

namespace Fernet\Component;

class FernetStylesheet
{
    public bool $preventWrapper = true;

    public function __toString(): string
    {
        return <<<EOCSS
            <style>
            body{
              overflow-x: hidden;
              font-family: Helvetica, Arial, sans-serif;
              font-size: 1.2em;
              line-height: 1.5em;
              margin: auto;
              max-width: 800px;
            }
            body.welcome {
              display: flex;
              align-items: center;
              color: #365094;
              margin-top: 45px;
            }
            h1, .welcome strong, .show-error h3 { color: #706A6A }
            .main { align-content: stretch }
            body.show-error { margin-top: 20px }
            .show-error .icon { display: inline-flex; align-self: center }
            .show-error .icon svg { height:1em; width:1em }
            .show-error .icon.baseline svg { top: .125em; position: relative }
            .show-error h1 { font-size: 1.5em }
            .show-error h2 { font-size: 2em; line-height: 1.2em; color: #C23}
            </style>
EOCSS;
    }
}
