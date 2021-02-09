<?php

namespace Fernet\Tests;

use DateTime;
use Fernet\Params;
use stdClass;

class ParamsTest extends TestCase
{
    public function testComponentStringFormat(): void
    {
        self::assertEquals(
            'entity={stdClass#0} time={DateTime#1}',
            Params::component(['entity' => new StdClass(), 'time' => new DateTime()])
        );
    }

    public function testSetAndGet(): void
    {
        $object = new stdClass();
        $object->foo = 'bar';
        $key = Params::set('object', $object);
        self::assertStringStartsWith(
           'object#',
           $key
       );
        self::assertSame(
           $object,
           Params::get($key)
       );
    }
}
