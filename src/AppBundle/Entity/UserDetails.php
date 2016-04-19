<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Entity;


use Commercetools\Core\Model\Customer\Customer;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class UserDetails
{
    private $firstName;
    private $lastName;
    private $email;
    private $password;
    private $currentPassword;

    /**
     * @return mixed
     */
    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }

    /**
     * @param mixed $currentPassword
     */
    public function setCurrentPassword($currentPassword)
    {
        $this->currentPassword = $currentPassword;
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
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
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
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
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
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('firstName', new NotBlank());
        $metadata->addPropertyConstraint('firstName', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('lastName', new NotBlank());
        $metadata->addPropertyConstraint('lastName', new Length(['min' => 3, 'max' => 255]));

        $metadata->addPropertyConstraint('email', new NotBlank());
        $metadata->addPropertyConstraint('email', new Length(['min' => 3, 'max' => 255]));
        $metadata->addPropertyConstraint('email', new Email());

    }

    public static function ofCustomer(Customer $customer)
    {
        $userDetails = new static();
        $userDetails->setFirstName($customer->getFirstName())
            ->setLastName($customer->getLastName())
            ->setEmail($customer->getEmail());

        return $userDetails;
    }
}
