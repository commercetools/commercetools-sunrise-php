<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Core\Model\Common\Address as ApiAddress;
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
    public $postalCode;

    public function __construct()
    {
        $this->salutations = new ListObject();
        $this->countries = new ListObject();
    }

    public static function fromCartAddress(ApiAddress $apiAddress)
    {
        $address = new static();
        $address->firstName = $apiAddress->getFirstName();
        $address->lastName = $apiAddress->getLastName();
        $address->streetName = $apiAddress->getStreetName();
        $address->city = $apiAddress->getCity();
        $address->region = $apiAddress->getRegion();
        $address->phone = $apiAddress->getPhone();
        $address->email = $apiAddress->getEmail();
        $address->postalCode = $apiAddress->getPostalCode();
        
        return $address;
    }
}
