<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class Location extends ViewData
{
    protected $language;

    public function __construct($language)
    {
        $this->language = $language;
    }
}
