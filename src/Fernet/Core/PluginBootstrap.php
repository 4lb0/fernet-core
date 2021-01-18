<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Framework;

abstract class PluginBootstrap
{
    abstract public function setUp(Framework $framework): void;

    public function addComponentNamespace(string $namespace): void
    {
        $namespace .= '\\Component';
        Framework::getInstance()->addOption('componentNamespaces', $namespace);
    }
}
