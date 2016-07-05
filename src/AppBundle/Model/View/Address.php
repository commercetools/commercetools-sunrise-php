<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Sunrise\AppBundle\Model\ViewData;

class Address extends ViewData
{
    /**
     * @var ListObject
     */
    public $salutations;
    public $firstName;
    public $lastName;
    public $streetName;
    public $streetNumber;
    public $city;
    public $region;
    /**
     * @var ListObject
     */
    public $countries;
    public $phone;
    public $email;
    public $cart;

    public function __construct()
    {
        $this->salutations = new ListObject();
        $this->countries = new ListObject();
    }
}
