<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Config;
use Fernet\Core\PluginLoader;
use Fernet\Framework;
use Fernet\Tests\TestCase;

class PluginLoaderTest extends TestCase
{
    public function testNoPlugins(): void
    {
        $framework = Framework::setUp();
        $pluginLoader = new PluginLoader($framework, $this->createNullLogger(), new Config());
        self::assertEquals([], $pluginLoader->warmUpPlugins());
    }

    public function testLoadPlugins(): void
    {
        $rootPath = dirname(__DIR__, 3).'/fixtures/';
        $framework = Framework::setUp([
            'rootPath' => $rootPath,
        ]);
        $config = new Config();
        $config-> plugins = ['acme/package'];
        $pluginLoader = new PluginLoader($framework, $this->createNullLogger(), $config);
        $pluginLoader->load();
        self::assertTrue(MyPluginTest::$pluginLoaded);
    }
}
