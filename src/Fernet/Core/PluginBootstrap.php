<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Framework;

abstract class PluginBootstrap
{
    abstract public function setUp(Framework $framework): void;

    public function addComponentNamespace(string $path): void
    {
        $path .= DIRECTORY_SEPARATOR.'Component';
        Framework::getInstance()->addOption('componentNamespaces', $path);
    }
}
