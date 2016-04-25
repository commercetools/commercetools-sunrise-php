<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;


use Commercetools\Sunrise\AppBundle\Model\ViewData;

class Header extends ViewData
{
    protected $title;
    public $location;
    public $user;
    public $miniCart;
    public $navMenu;

    public function __construct($title)
    {
        $this->title = $title;
    }
}
