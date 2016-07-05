<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class AddressForm extends ViewData
{
    public $billingAddressDifferentToBillingAddress;
    /**
     * @var Address
     */
    public $shippingAddress;
    /**
     * @var Address
     */
    public $billingAddress;
    /**
     * @var ErrorCollection
     */
    public $errors;

    public function __construct()
    {
        $this->shippingAddress = new Address();
        $this->billingAddress = new Address();
        $this->errors = new ErrorCollection();
    }
}
