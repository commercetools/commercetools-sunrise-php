<?php
/**
 * @author: @Ylambers <yaron.lambers@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\DependencyInjection\Factory;


use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class SecurityFactory extends FormLoginFactory
{
    public function getKey()
    {
        return 'ctp-login';
    }

    protected function getListenerId()
    {
        return 'security.authentication.listener.form';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'security.authentication_provider.ctp.'.$id;
        $container
            ->setDefinition($provider, new DefinitionDecorator('security.authentication_provider.ctp'))
            ->replaceArgument(1, new Reference($userProviderId))
            ->replaceArgument(3, $id);

        return $provider;
    }

}