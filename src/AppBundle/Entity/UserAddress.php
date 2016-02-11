<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Entity;


use Commercetools\Core\Model\Common\Address;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class UserAddress
{
    private $firstName;
    private $lastName;
    private $streetName;
    private $streetNumber;
    private $postalCode;
    private $city;
    private $region;
    private $country;
    private $company;
    private $phone;
    private $email;
    private $title;
    public $password;

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return mixed
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * @param mixed $streetName
     */
    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;
    }

    /**
     * @return mixed
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * @param mixed $streetNumber
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param mixed $postalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param mixed $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('firstName', new NotBlank());
        $metadata->addPropertyConstraint('firstName', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('lastName', new NotBlank());
        $metadata->addPropertyConstraint('lastName', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('streetName', new NotBlank());
        $metadata->addPropertyConstraint('streetName', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('streetNumber', new NotBlank());
        $metadata->addPropertyConstraint('streetNumber', new Length(['min' => 1, 'max' => 255]));

        $metadata->addPropertyConstraint('postalCode', new NotBlank());
        $metadata->addPropertyConstraint('postalCode', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('city', new NotBlank());
        $metadata->addPropertyConstraint('city', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('region', new NotBlank());
        $metadata->addPropertyConstraint('region', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('country', new NotBlank());
        $metadata->addPropertyConstraint('country', new Length(['min' => 2, 'max' => 2]));

        $metadata->addPropertyConstraint('company', new NotBlank());
        $metadata->addPropertyConstraint('company', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('phone', new NotBlank());
        $metadata->addPropertyConstraint('phone', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('email', new NotBlank());
        $metadata->addPropertyConstraint('email', new Email() );
        $metadata->addPropertyConstraint('email', new Length(['min' => 5, 'max' => 255]));

        $metadata->addPropertyConstraint('title', new NotBlank());
        $metadata->addPropertyConstraint('title', new Length(['min' => 3, 'max' => 255]));
    }

    /**
     * @param Address $address
     * @return static
     */
    public static function ofAddress(Address $address)
    {
        $userAddress = new static();
        $userAddress->setFirstName($address->getFirstName());
        $userAddress->setLastName($address->getLastName());
        $userAddress->setCompany($address->getCompany());
        $userAddress->setEmail($address->getEmail());
        $userAddress->setTitle($address->getTitle());

        $userAddress->setStreetName($address->getStreetName());
        $userAddress->setStreetNumber($address->getStreetNumber());
        $userAddress->setPostalCode($address->getPostalCode());
        $userAddress->setCity($address->getCity());
        $userAddress->setRegion($address->getRegion());
        $userAddress->setCountry($address->getCountry());
        $userAddress->setPhone($address->getPhone());

        return $userAddress;
    }

    /**
     * @return Address
     */
    public function toCTPAddress()
    {
        $newAddress = Address::of();
        $newAddress->setFirstName($this->getFirstName());
        $newAddress->setLastName($this->getLastName());
        $newAddress->setCompany($this->getCompany());
        $newAddress->setEmail($this->getEmail());
        $newAddress->setTitle($this->getTitle());
        $newAddress->setStreetName($this->getStreetName());
        $newAddress->setStreetNumber($this->getStreetNumber());
        $newAddress->setPostalCode($this->getPostalCode());
        $newAddress->setCity($this->getCity());
        $newAddress->setRegion($this->getRegion());
        $newAddress->setCountry($this->getCountry());
        $newAddress->setPhone($this->getPhone());

        return $newAddress;
    }
}
