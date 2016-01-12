<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\AppBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app')
            ->children()
                ->arrayNode('commercetools')
                    ->children()
                        ->scalarNode('client_id')->end()
                        ->scalarNode('client_secret')->end()
                        ->scalarNode('project')->end()
                    ->end()
                ->end()
                ->arrayNode('fallback_languages')
                    ->prototype('array')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->arrayNode('languages')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('countries')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('currencies')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('sunrise')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
