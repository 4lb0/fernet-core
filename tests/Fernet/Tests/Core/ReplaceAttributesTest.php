<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\ReplaceAttributes;
use Fernet\Framework;
use Fernet\Tests\TestCase;

class ReplaceAttributesTest extends TestCase
{
    public function testReplaceDontBreak(): void
    {
        $replace = Framework::getInstance()->getContainer()->get(ReplaceAttributes::class);
        $html = '<a href="/hello/world">Hello</a>';
        $component = $this->createComponent();
        self::assertEquals($html, $replace->replace($html, $component));
    }

    public function testReplaceLink(): void
    {
        $replace = Framework::getInstance()->getContainer()->get(ReplaceAttributes::class);
        $html = '<a @href="handler">Hello</a>';
        $component = $this->createComponent();
        self::assertStringContainsString('href', $replace->replace($html, $component));

        $vars = serialize(['1', 2, false]);
        $html = "<a @href='otherHandler($vars)'>Hello</a>";
        $component = $this->createComponent();
        self::assertStringContainsString('href', $replace->replace($html, $component));
    }

    public function testReplaceInput(): void
    {
        $replace = Framework::getInstance()->getContainer()->get(ReplaceAttributes::class);
        $html = '<input @bind="name">';
        $component = $this->createComponent();
        $component->name = 'John';
        self::assertStringContainsString('value="John"', $replace->replace($html, $component));
    }

    public function testReplaceTextarea(): void
    {
        $replace = Framework::getInstance()->getContainer()->get(ReplaceAttributes::class);
        $html = '<textarea @bind="text"></textarea>';
        $component = $this->createComponent();
        $component->text = 'Hello World';
        self::assertStringContainsString('>Hello World</textarea>', $replace->replace($html, $component));
    }
}
