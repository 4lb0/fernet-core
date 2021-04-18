<?php

namespace Fernet\Tests\Core;

use Fernet\Core\CaseConverter;
use Fernet\Tests\TestCase;

class CaseConverterTest extends TestCase
{
    public function testCamelCase(): void
    {
        self::assertEquals(
            'helloWorld',
            CaseConverter::camelCase('hello-world')
        );
        self::assertEquals(
            'anotherGoodExample',
            CaseConverter::camelCase('another__good__example')
        );
    }

    public function testPascalCase(): void
    {
        self::assertEquals(
            'HelloWorld',
            CaseConverter::pascalCase('hello-world')
        );
        self::assertEquals(
            'AnotherGoodExample',
            CaseConverter::pascalCase('another__good__example')
        );
    }

    public function testKebab(): void
    {
        self::assertEquals(
            'hello-world',
            CaseConverter::kebab('HelloWorld')
        );
        self::assertEquals(
            'another-good-example',
            CaseConverter::kebab('AnotherGoodExample')
        );
    }

    public function testNamespaces(): void
    {
        self::assertEquals(
            'some.namespace.here',
            CaseConverter::kebab('Some\\Namespace\\Here')
        );
        self::assertEquals(
            'Some\\Namespace\\Here',
            CaseConverter::pascalCase('some.namespace.here')
        );
    }
}
