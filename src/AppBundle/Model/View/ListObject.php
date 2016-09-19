<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;

class ListObject extends ViewData
{
    public $list;

    public function __construct()
    {
        $this->list = new ViewDataCollection();
    }
}
