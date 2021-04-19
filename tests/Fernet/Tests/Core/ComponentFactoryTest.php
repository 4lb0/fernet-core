<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\ComponentFactory;
use Fernet\Core\NotFoundException;
use Fernet\Framework;
use Fernet\Tests\TestCase;

class ComponentFactoryTest extends TestCase
{
    private ComponentFactory $componentFactory;

    public function setUp(): void
    {
        $this->componentFactory = new ComponentFactory(Framework::getInstance());
    }

    /**
     * @throws NotFoundException
     */
    public function testCreateFullyQualifier(): void
    {
        $class = FactoryTestComponent::class;
        $component = $this->componentFactory->create($class);
        self::assertEquals($component, new FactoryTestComponent());
    }

    public function testCreateNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->componentFactory->create('ThisComponentNotExists');
    }

    /**
     * @throws NotFoundException
     */
    public function testCreateNotDuplicates(): void
    {
        $class = FactoryTestComponent::class;
        $component = $this->componentFactory->create($class);
        self::assertNotSame($component, $this->componentFactory->create($class));
    }
}

class FactoryTestComponent
{
    public string $attribute;

    public function __toString()
    {
        return "<p>$this->attribute</p>";
    }
}
