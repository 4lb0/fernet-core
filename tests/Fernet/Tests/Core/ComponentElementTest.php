<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\ComponentElement;
use Fernet\Framework;
use PHPUnit\Framework\TestCase;

class ComponentElementTest extends TestCase
{
    public function setUp(): void
    {
        // TODO This should be in the phpunit bootstrap
        Framework::setUp()->addOption('componentNamespaces', 'Fernet\Tests\Component');
    }

    public function testRenderSimpleTag(): void
    {
        self::assertEquals(
            '<div id="_fernet_component_0" class="_fernet_component"><p class="something">Simple static tag</p></div>',
            (new ComponentElement('SimpleStaticTag'))->render()
        );
    }
}
