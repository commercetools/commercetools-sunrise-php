<?php

namespace Commercetools\Sunrise;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */
class BehatContext extends MinkContext implements SnippetAcceptingContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given I navigate to :arg1
     */
    public function iNavigateTo($arg1)
    {
        $this->visit($arg1);
    }

    /**
     * @Then I see the text :arg1
     */
    public function iSeeTheText($arg1)
    {
        $this->assertPageContainsText($arg1);
    }

    /**
     * @Then the parameter :name should be :value
     */
    public function theParameterShouldBe($name, $value)
    {
        $request = $this->getRequestByUri($this->getSession()->getCurrentUrl());
        assertSame($value, $request->get($name));
    }

    /**
     * @Then the parameter :name should not be :value
     */
    public function theParameterShouldNotBe($name, $value)
    {
        $request = $this->getRequestByUri($this->getSession()->getCurrentUrl());
        assertNotSame($value, $request->get($name));
    }

    protected function getRequestByUri($uri)
    {
        return Request::create($uri);
    }

    /**
     * @Then /^the link "(?P<link>(?:[^"]|\\")*)" should have class "(?P<class>[^"]+)"$/
     */
    public function theLinkShouldHaveClass($link, $class)
    {
        $link = $this->getSession()->getPage()->findLink($link);
        $link->hasClass($class);
    }

    /**
     * @When /^(?:|I )follow the "(?P<element>[^"]*)" link$/
     * @When /^(?:|I )press the "(?P<element>[^"]*)" element/
     */
    public function clickPatternElement($element)
    {
        $element = $this->assertSession()->elementExists('css', $this->fixStepArgument($element));
        $element->click();
    }

    /**
     * @When /^(?:|I )add the product to cart$/
     */
    public function iAddTheProductToCart()
    {
        $element = $this->assertSession()->elementExists('css', '.add-to-bag');
        $element->click();
    }

    /**
     * @Given I start a new session
     */
    public function restartSession()
    {
        $this->getSession()->reset();
    }

    /**
     * @Given I wait for :seconds
     */
    public function waitFor($seconds)
    {
        $toWait = $seconds * 1000;
        $this->getSession()->wait($toWait);
    }
}
