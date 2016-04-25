<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class MiniCart extends ViewData
{
    protected $totalItems;

    public function __construct($totalItems)
    {
        $this->totalItems = $totalItems;
    }
}
