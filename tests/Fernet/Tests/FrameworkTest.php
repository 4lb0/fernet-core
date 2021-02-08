<?php
declare(strict_types=1);

namespace Fernet\Tests;

use Fernet\Framework;

class FrameworkTest extends TestCase
{
    public function testConfigNotExists(): void
    {
        self::assertNull(Framework::getInstance()->getConfig('configNotExists'));
    }
    public function testSetConfig(): void
    {
        $framework = Framework::getInstance();
        self::assertSame($framework, $framework->setConfig('error404', 'SomeErrorComponent'));
        self::assertEquals('SomeErrorComponent', $framework->getConfig('error404'));
    }
}
