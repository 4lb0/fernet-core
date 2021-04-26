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
            .__fernet_skeleton * {
              opacity: 0.8;
              content: "";
              color: transparent !important;
              background-image: linear-gradient(to right, #d9d9d9 0%, rgba(0,0,0,0.05) 20%, #d9d9d9 40%, #d9d9d9 100%);
              background-repeat: no-repeat;
              background-size: 450px 400px;
              animation: shimmer 0.3s linear infinite;
            }
            @keyframes shimmer {
              0%{
                background-position: -450px 0;
              }
              100%{
                background-position: 450px 0;
              }
            }
CSS;
    }

    public function __toString()
    {
        return '<script src="/js/fernet.js" defer async></script>';
    }
}
