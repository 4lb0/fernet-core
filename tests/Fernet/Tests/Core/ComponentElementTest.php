<?php
declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\ComponentElement;
use Fernet\Tests\TestCase;

class ComponentElementTest extends TestCase
{
    public function testGetComponent(): void
    {
        $component = $this->createComponent();
        self::assertSame($component, (new ComponentElement($component))->getComponent());
    }
}
