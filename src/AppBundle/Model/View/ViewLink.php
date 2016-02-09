<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class ViewLink extends ViewData
{
    protected $href;

    public function __construct($url)
    {
        $this->href = $url;
    }
}
