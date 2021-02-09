<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\ReplaceComponents;
use Fernet\Framework;
use Fernet\Tests\TestCase;

class ReplaceComponentsTest extends TestCase
{
    public function setUp(): void
    {
        Framework::getInstance()->addConfig('componentNamespaces', __NAMESPACE__);
    }

    public function testReplace(): void
    {
        $replace = new ReplaceComponents();
        self::assertEquals(
            '<div><p>Hello World</p></div>',
            $replace->replace('<div><TestReplaceComponent /></div>')
        );
    }

    public function testAttribute(): void
    {
        $replace = new ReplaceComponents();
        self::assertEquals(
            '<div><p>Hello John</p></div>',
            $replace->replace('<div><TestReplaceComponent name="John" /></div>')
        );
    }
}

class TestReplaceComponent
{
    public string $name = 'World';

    public function __toString(): string
    {
        return "<p>Hello $this->name</p>";
    }
}
