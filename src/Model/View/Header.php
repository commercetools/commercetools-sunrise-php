<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Model\View;


use Commercetools\Sunrise\Model\ViewData;

class Header extends ViewData
{
    private $title;

    public function __construct($title)
    {
        $this->title = $title;
    }
}
