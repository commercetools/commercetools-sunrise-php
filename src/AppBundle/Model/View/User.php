<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class User extends ViewData
{
    protected $isLoggedIn;
    protected $signIn;

    public function __construct($signIn, $isLoggedIn)
    {
        $this->isLoggedIn = $isLoggedIn;
        $this->signIn = $signIn;
    }
}
