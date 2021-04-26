<?php

declare(strict_types=1);

namespace Fernet\Component;

use Fernet\Core\ComponentElement;
use Fernet\Framework;

class FernetJs
{
    public bool $preventWrapper = true;

    public function __construct(Framework $framework, FernetStylesheet $stylesheet)
    {
        $framework->setConfig('enableJs', true);
        $stylesheet->add($this->getStyles(), static::class);
    }

    public function getStyles(): string
    {
        $wrapper = ComponentElement::WRAPPER_CLASS;

        return <<<CSS
            .$wrapper { 
                display: inline-block;
            }
CSS;
    }

    public function __toString()
    {
        return '<script src="/js/fernet.js" defer async></script>';
    }
}
