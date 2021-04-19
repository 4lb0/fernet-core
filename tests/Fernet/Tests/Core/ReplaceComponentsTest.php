<?php /** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */
/** @noinspection ALL */

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

    public function testMultipleAttribute(): void
    {
        $replace = new ReplaceComponents();
        self::assertEquals(
            '<div><p>Hi John</p></div>',
            $replace->replace('<div><TestReplaceComponent greeting="hi" name="John" /></div>')
        );
    }

    public function testEmptyAttribute(): void
    {
        $replace = new ReplaceComponents();
        self::assertEquals(
            '<p>This is not valid</p>',
            $replace->replace('<p><TestEmptyAttribute /></p>')
        );
        self::assertEquals(
            '<p>Valid is true</p>',
            $replace->replace('<p><TestEmptyAttribute valid /></p>')
        );
    }
}

class TestReplaceComponent
{
    public string $greeting = "hello";
    public string $name = 'World';

    public function __toString(): string
    {
        $greeting = ucfirst($this->greeting);
        return "<p>$greeting $this->name</p>";
    }
}

class TestEmptyAttribute
{
    public bool $valid = false;

    public function __toString(): string
    {
        return $this->valid ? 'Valid is true' : 'This is not valid';
    }
}
