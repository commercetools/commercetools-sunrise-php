<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\View;

use Commercetools\Sunrise\Model\ViewData;

class ViewLink extends ViewData
{
    protected $href;

    public function __construct($url)
    {
        $this->href = $url;
    }
}
