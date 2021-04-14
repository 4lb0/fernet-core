<?php

declare(strict_types=1);

namespace Fernet\Core;

use Exception as Base;

class Exception extends Base
{
    private string $link;

    public function __construct(?string $message = null, int $code = 0, string $link = '')
    {
        parent::__construct($message, $code);
        $this->link = $link;
    }

    public function getLink(): string
    {
        return $this->link;
    }
}
