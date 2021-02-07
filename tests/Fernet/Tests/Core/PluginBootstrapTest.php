<?php
declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\PluginBootstrap;
use Fernet\Framework;
use PHPUnit\Framework\TestCase;

class PluginBootstrapTest extends TestCase
{
    public function testAddComponentNamespace()
    {
        $framework = Framework::setUp([]);
        $stub = $this->getMockForAbstractClass(PluginBootstrap::class);
        $stub->addComponentNamespace('PluginTest');
        self::assertContains('PluginTest\\Component', $framework->getConfig('componentNamespaces'));
    }
}
