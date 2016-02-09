<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */

// src/AppBundle/Security/User/WebserviceUser.php
namespace Commercetools\Sunrise\AppBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class CTPUser implements UserInterface, EquatableInterface
{
    private $username;
    private $password;
    private $roles;
    private $id;

    public function __construct($username, $password, array $roles, $id)
    {
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles;
        $this->id= $id;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        // not needed;
    }

    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof CTPUser) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
