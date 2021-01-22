<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Fernet\Framework;
use Symfony\Component\HttpFoundation\Response;
use function PHPUnit\Framework\assertEquals;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private Response $response;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $framework = Framework::getInstance();
        $framework->setConfig('enableJs', false);
        $framework->setConfig('logPath', '/dev/null');
    }

    /**
     * @Given /^the \w+ defined in the class$/
     */
    public function classDefinition(PyStringNode $classDefinition): void
    {
        eval($classDefinition->getRaw());
    }

    /**
     * @When /^the framework is run with component "([^"]*)"$/
     */
    public function isRunTheFrameworkWithTheComponent(string $component): void
    {
        $this->response = Framework::getInstance()->run($component);
    }

    /**
     * @Then /^the output is \'([^\']*)\'$/
     */
    public function theOutputIs(string $html): void
    {
        assertEquals($html, $this->response->getContent());
        assertEquals(200, $this->response->getStatusCode());
    }

    /**
     * @Then /^the output is an error (\d+)$/
     */
    public function theOutputIsAnError(int $status)
    {
        assertEquals($status, $this->response->getStatusCode());
    }

}
