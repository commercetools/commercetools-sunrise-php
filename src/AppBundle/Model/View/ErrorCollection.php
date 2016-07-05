<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;

class ErrorCollection extends ViewData
{
    /**
     * @var ViewDataCollection
     */
    public $globalErrors;

    public function __construct()
    {
        $this->globalErrors = new ViewDataCollection();
    }
}
