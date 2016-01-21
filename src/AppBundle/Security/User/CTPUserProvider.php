<?php
/**
 * @author: @Ylambers <yaron.lambers@commercetools.de>
 */


namespace Commercetools\Sunrise\AppBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class CTPUserProvider implements UserProviderInterface
{
    public function loadUserByUsername($username)
    {

        $userData = true;

        if ($userData) {

            $username = 'ylambers';
            $password = 'yaron';
            $roles = ['ROLE_USER'];

            return new CTPUser($username, $password, $roles);
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof CTPUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Commercetools\Sunrise\AppBundle\Security\User\CTPUser';
    }
}

