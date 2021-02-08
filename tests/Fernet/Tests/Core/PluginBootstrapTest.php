<?php
declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\PluginBootstrap;
use Fernet\Framework;
use Fernet\Tests\TestCase;

class PluginBootstrapTest extends TestCase
{
    public function testAddComponentNamespace(): void
    {
        $framework = Framework::setUp([]);
        $stub = $this->getMockForAbstractClass(PluginBootstrap::class);
        $stub->addComponentNamespace('PluginTest');
        self::assertContains('PluginTest\\Component', $framework->getConfig('componentNamespaces'));
    }
}
