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
                ->arrayNode('cache')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('sunrise')
                    ->children()
                        ->scalarNode('assetsPath')->end()
                        ->arrayNode('itemsPerPage')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('cart')
                            ->children()
                                ->arrayNode('attributes')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('products')
                            ->children()
                                ->arrayNode('facets')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('attribute')->end()
                                            ->scalarNode('multi')->defaultValue(true)->end()
                                            ->scalarNode('display')->defaultValue('2column')->end()
                                            ->scalarNode('type')->defaultValue('enum')->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('sort')
                                    ->prototype('array')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('variantsSelector')
                                    ->prototype('array')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('details')
                                    ->children()
                                        ->arrayNode('attributes')
                                            ->prototype('array')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
