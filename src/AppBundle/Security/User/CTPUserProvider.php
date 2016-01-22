<?php
/**
 * @author: @Ylambers <yaron.lambers@commercetools.de>
 */


namespace Commercetools\Sunrise\AppBundle\Security\User;

use Commercetools\Core\Client;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class CTPUserProvider implements UserProviderInterface
{

    public function loadUserByUsername($username)
    {
        return new CTPUser($username, '', ['ROLE_USER']);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof CTPUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return $class === 'Commercetools\Sunrise\AppBundle\Security\User\CTPUser';
    }
}

