<?php

namespace Fernet\Tests\Core;

use Fernet\Core\PluginBootstrap;
use Fernet\Framework;

class MyPluginTest extends PluginBootstrap
{
    public static bool $pluginLoaded = false;

    public function setUp(Framework $framework): void
    {
        static::$pluginLoaded = true;
    }
}
