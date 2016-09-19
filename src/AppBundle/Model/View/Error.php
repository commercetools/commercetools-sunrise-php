<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class Error extends ViewData
{
    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}
