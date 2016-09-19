<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;
use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Sunrise\AppBundle\Model\ViewDataCollection;

class ViewCart extends ViewData
{
    
    public $totalItems;
    public $salesTax;
    public $subtotalPrice;
    public $totalPrice;
    public $shippingMethod;
    /**
     * @var ListObject
     */
    public $lineItems;

    public function __construct()
    {
        $this->lineItems = new ListObject();
    }
}
