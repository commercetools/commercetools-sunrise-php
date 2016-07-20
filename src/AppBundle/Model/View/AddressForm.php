<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Core\Model\Cart\Cart;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Sunrise\AppBundle\Model\ViewData;

class AddressForm extends ViewData
{
    public $billingAddressDifferentToBillingAddress = false;
    public $titleShipping;
    public $firstNameShipping;
    public $lastNameShipping;
    public $streetNameShipping;
    public $additionalStreetInfoShipping;
    public $cityShipping;
    public $regionShipping;
    public $postalCodeShipping;
    public $countryShipping;
    public $phoneShipping;
    public $emailShipping;
    public $titleBilling;
    public $firstNameBilling;
    public $lastNameBilling;
    public $streetNameBilling;
    public $additionalStreetInfoBilling;
    public $cityBilling;
    public $regionBilling;
    public $postalCodeBilling;
    public $countryBilling;
    public $phoneBilling;
    public $emailBilling;


    /**
     * @var ErrorCollection
     */
    public $errors;

    public function __construct()
    {
        $this->errors = new ErrorCollection();
    }

    public static function fromCart(Cart $cart)
    {
        $addressForm = new static();
        if (!is_null($cart->getShippingAddress())) {
            $addressForm->titleShipping = $cart->getShippingAddress()->getTitle();
            $addressForm->firstNameShipping = $cart->getShippingAddress()->getFirstName();
            $addressForm->lastNameShipping = $cart->getShippingAddress()->getLastName();
            $addressForm->streetNameShipping = $cart->getShippingAddress()->getStreetName();
            $addressForm->additionalStreetInfoShipping = $cart->getShippingAddress()->getAdditionalStreetInfo();
            $addressForm->cityShipping = $cart->getShippingAddress()->getCity();
            $addressForm->regionShipping = $cart->getShippingAddress()->getRegion();
            $addressForm->postalCodeShipping = $cart->getShippingAddress()->getPostalCode();
            $addressForm->countryShipping = $cart->getShippingAddress()->getCountry();
            $addressForm->phoneShipping = $cart->getShippingAddress()->getPhone();
            $addressForm->emailShipping = $cart->getShippingAddress()->getEmail();
        }
        if (!is_null($cart->getBillingAddress())) {
            $addressForm->billingAddressDifferentToBillingAddress = true;
            $addressForm->titleBilling = $cart->getBillingAddress()->getTitle();
            $addressForm->firstNameBilling = $cart->getBillingAddress()->getFirstName();
            $addressForm->lastNameBilling = $cart->getBillingAddress()->getLastName();
            $addressForm->streetNameBilling = $cart->getBillingAddress()->getStreetName();
            $addressForm->additionalStreetInfoBilling = $cart->getBillingAddress()->getAdditionalStreetInfo();
            $addressForm->cityBilling = $cart->getBillingAddress()->getCity();
            $addressForm->regionBilling = $cart->getBillingAddress()->getRegion();
            $addressForm->postalCodeBilling = $cart->getBillingAddress()->getPostalCode();
            $addressForm->countryBilling = $cart->getBillingAddress()->getCountry();
            $addressForm->phoneBilling = $cart->getBillingAddress()->getPhone();
            $addressForm->emailBilling = $cart->getBillingAddress()->getEmail();
        }

        return $addressForm;
    }

    public static function getAddress(array $data, $type)
    {
        $address = new Address();
        isset($data['firstName' . ucfirst($type)]) ? $address->setFirstName($data['firstName' . ucfirst($type)]):'';
        isset($data['lastName' . ucfirst($type)]) ? $address->setLastName($data['lastName' . ucfirst($type)]):'';
        isset($data['streetName' . ucfirst($type)]) ? $address->setStreetName($data['streetName' . ucfirst($type)]):'';
        isset($data['streetNumber' . ucfirst($type)]) ? $address->setStreetNumber($data['streetNumber' . ucfirst($type)]):'';
        isset($data['city' . ucfirst($type)]) ? $address->setCity($data['city' . ucfirst($type)]):'';
        isset($data['region' . ucfirst($type)]) ? $address->setRegion($data['region' . ucfirst($type)]):'';
        isset($data['postalCode' . ucfirst($type)]) ? $address->setPostalCode($data['postalCode' . ucfirst($type)]):'';
        isset($data['country' . ucfirst($type)]) ? $address->setCountry($data['countries' . ucfirst($type)]):'';
        isset($data['phone' . ucfirst($type)]) ? $address->setPhone($data['phone' . ucfirst($type)]):'';
        isset($data['email' . ucfirst($type)]) ? $address->setEmail($data['email' . ucfirst($type)]):'';

        return $address;
    }
}
