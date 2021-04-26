<?php

declare(strict_types=1);

namespace Fernet\Tests\Core;

use Fernet\Config;
use Fernet\Core\Exception;
use Fernet\Core\Router;
use Fernet\Core\Run;
use Fernet\Framework;
use Fernet\Tests\TestCase;
use Throwable;

class RunTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testShowError(): void
    {
        $component = $this->createComponent('some error');
        $framework = Framework::getInstance();
        $framework->setConfig('devMode', false);
        $config = new Config();
        $config->errorPages = ['error500' => $component];
        $run = new Run($framework->getContainer()->get(Router::class), $this->createNullLogger(), $config);
        self::assertEquals('some error', $run->showError(new Exception('message')));
    }

    /**
     * @throws Throwable
     */
    public function testShowErrorOnDevMode(): void
    {
        $this->expectException(Exception::class);
        $component = $this->createComponent('some error');
        $framework = Framework::getInstance();
        $framework->setConfig('devMode', true);
        $config = new Config();
        $config->errorPages = ['error500' => $component];
        $run = new Run($framework->getContainer()->get(Router::class), $this->createNullLogger(), $config);
        $run->showError(new Exception('message'));
    }
}
