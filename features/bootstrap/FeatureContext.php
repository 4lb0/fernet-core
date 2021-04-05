<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Fernet\Browser;
use Fernet\Framework;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertStringContainsString;
use Monolog\Logger;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
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
        file_put_contents('/tmp/fernet.log', ''); // clean log
        Framework::setUp([
            'logPath' => '/tmp/fernet.log',
            'logLevel' => Logger::DEBUG,
            'enableJs' => false,
            'devMode' => false,
        ]);
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

    /**
     * @When /^the main component is "([^"]*)" and we navigate to "([^"]*)"$/
     */
    public function theMainComponentIsAndWeNavigateTo($component, $url)
    {
        $this->browser = new Browser();
        $this->browser->setMainComponent($component);
        $this->crawler = $this->browser->request('GET', $url);
        file_put_contents('/tmp/asd.html', $this->crawler->html());
    }

    /**
     * @Given /^the link "([^"]*)" is clicked$/
     */
    public function theLinkIsClicked(string $link)
    {
        $this->crawler = $this->browser->clickLink($link);
    }

    /**
     * @Then /^I can see the text "([^"]*)" on "([^"]*)"$/
     */
    public function theICanSeeTheText(string $text, string $selector)
    {
        $html = $this->crawler->filter($selector)->html();
        assertStringContainsString($text, $html);
    }

}
