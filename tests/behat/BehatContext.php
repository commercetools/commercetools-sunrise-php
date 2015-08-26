<?php

namespace Commercetools\Sunrise;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;

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
}
