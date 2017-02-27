<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class ShippingForm extends ViewData
{
    public $shippingMethods;
    
     /**
     * @var ErrorCollection
     */
    public $errors;

    public function __construct()
    {
        $this->shippingMethods = new ListObject();
        $this->errors = new ErrorCollection();
    }

    public function addShippingMethod(ShippingMethod $method)
    {
        $this->shippingMethods->list->add($method);
    }
}
