<?php

declare(strict_types=1);

namespace Fernet\Tests;

use Fernet\Config;
use Fernet\Framework;
use Monolog\Logger;

class FrameworkTest extends TestCase
{
    public function testSetUp(): void
    {
        $_ENV['FERNET_LOG_LEVEL'] = Logger::EMERGENCY;
        $_ENV['FERNET_DEV_MODE'] = 'true';
        $framework = Framework::setUp();
        self::assertEquals(Logger::EMERGENCY, $framework->getConfig('logLevel'));
        self::assertTrue($framework->getConfig('devMode'));
    }

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

    public function testRun(): void
    {
        $framework = Framework::getInstance();
        $html = '<html lang="en"><body>Hello world</body></html>';
        $component = $this->createComponent($html);
        self::assertEquals($html, $framework->run($component)->getContent());
    }

    public function testRunNotFound(): void
    {
        $framework = Framework::setUp(['routingFile' => 'tests/fixtures/routing.json']);
        $notFoundComponent = $this->createComponent('not found');
        $framework->setConfig('devMode', false);
        $framework->getContainer()->get(Config::class)->errorPages = ['error404' => $notFoundComponent];
        $component = $this->createComponent();
        $request = $this->createRequest('/not/found');
        self::assertEquals('not found', $framework->run($component, $request)->getContent());
    }

}
