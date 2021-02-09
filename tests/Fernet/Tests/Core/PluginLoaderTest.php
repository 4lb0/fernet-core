<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\Exception;
use Fernet\Core\PluginBootstrap;
use Fernet\Core\PluginLoader;
use Fernet\Framework;
use Fernet\Tests\TestCase;

class PluginLoaderTest extends TestCase
{
    public function testNoPluginFile(): void
    {
        $framework = Framework::setUp(['pluginFile' => 'file/not/exists.json']);
        $pluginLoader = new PluginLoader($framework, $this->createNullLogger());
        self::assertEquals([], $pluginLoader->warmUpPlugins());
    }

    public function testLoadPlugins(): void
    {
        $rootPath = dirname(dirname(dirname(__DIR__))).'/fixtures/';
        $framework = Framework::setUp([
            'pluginFile' => 'plugins.json',
            'rootPath' => $rootPath,
        ]);
        $pluginLoader = new PluginLoader($framework, $this->createNullLogger());
        self::assertEquals(null, $pluginLoader->loadPlugins());
    }

    public function testPluginFileJsonError(): void
    {
        $this->expectException(Exception::class);
        $framework = Framework::setUp();
        $framework->setConfig('pluginFile', 'tests/fixtures/non-json-file.json');
        (new PluginLoader($framework, $this->createNullLogger()))->warmUpPlugins();
    }
}

class MyPluginTest extends PluginBootstrap
{
}
