<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Fernet\Browser;
use Fernet\Framework;
use Monolog\Logger;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringContainsString;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private const LOG_FILE = 'fernet_features.log';
    private Response $response;
    private Browser $browser;
    private ?Crawler $crawler;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $logFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.static::LOG_FILE;
        file_put_contents($logFile, ''); // clean log
        Framework::setUp([
            'logPath' => $logFile,
            'logLevel' => Logger::DEBUG,
            'enableJs' => false,
            'devMode' => false,
        ]);
        $this->browser = new Browser();
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
    public function theOutputIsAnError(int $status): void
    {
        assertEquals($status, $this->response->getStatusCode());
    }

    /**
     * @When /^the main component is "([^"]*)"$/
     */
    public function theMainComponentIs(string $component): void
    {
        $this->browser->setMainComponent($component);
    }

    /**
     * @When /^go to "([^"]*)"$/
     */
    public function goTo(string $url): void
    {
        $this->crawler = $this->browser->request('GET', $url);
    }

    /**
     * @Given /^the link "([^"]*)" is clicked$/
     */
    public function theLinkIsClicked(string $link): void
    {
        $this->crawler = $this->browser->clickLink($link);
    }

    /**
     * @Then /^I can see the text "([^"]*)" on "([^"]*)"$/
     */
    public function theICanSeeTheText(string $text, string $selector): void
    {
        $html = $this->crawler->filter($selector)->html();
        assertStringContainsString($text, $html);
    }
}
