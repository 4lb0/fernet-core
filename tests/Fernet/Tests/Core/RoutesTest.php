<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Core\Routes;
use Fernet\Framework;
use PHPUnit\Framework\TestCase;

class RoutesTest extends TestCase
{
    public function testNoRoutesFile(): void
    {
        $framework = Framework::setUp(['routingFile' => 'file/not/exists.json']);
        $routes = new Routes($framework);
        self::assertEquals([], $routes->getRoutes());
    }
}
