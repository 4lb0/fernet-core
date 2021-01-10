<?php


namespace Fernet\Tests\Core;


use Fernet\Core\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testCamelCase(): void
    {
        self::assertEquals(
            'helloWorld',
            Helper::camelCase('hello-world')
        );
        self::assertEquals(
            'anotherGoodExample',
            Helper::camelCase('another__good__example')
        );
    }

    public function testPascalCase(): void
    {
        self::assertEquals(
            'HelloWorld',
            Helper::pascalCase('hello-world')
        );
        self::assertEquals(
            'AnotherGoodExample',
            Helper::pascalCase('another__good__example')
        );
    }

    public function testHyphen(): void
    {

        self::assertEquals(
            'hello-world',
            Helper::hyphen('HelloWorld')
        );
        self::assertEquals(
            'another-good-example',
            Helper::hyphen('AnotherGoodExample')
        );
    }
}