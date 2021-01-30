<?php

declare(strict_types=1);

namespace Fernet\Core;

use Fernet\Framework;

abstract class PluginBootstrap
{
    public function setUp(Framework $framework): void
    {
    }

    public function install(Framework $framework): void
    {
    }

    public function addComponentNamespace(string $namespace): void
    {
        $namespace .= '\\Component';
        Framework::getInstance()->addConfig('componentNamespaces', $namespace);
    }
}
