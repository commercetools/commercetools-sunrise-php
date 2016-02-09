<?php
/**
 * @author: Ylambers <yaron.lambers@commercetools.de>
 */


namespace Commercetools\Sunrise\AppBundle\Security\User;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class CTPUserProvider implements UserProviderInterface
{

    private $session;

    /**
     * CTPUserProvider constructor.
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function loadUserByUsername($username)
    {
        $id = $this->session->get('customer.id');

        return new CTPUser($username, '', ['ROLE_USER'], $id);
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

