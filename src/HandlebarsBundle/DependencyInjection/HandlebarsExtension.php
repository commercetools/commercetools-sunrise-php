<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\HandlebarsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class HandlebarsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('handlebars.xml');

        $configuration = new Configuration();

        $handlebarsFilesystemLoaderDefinition = $container->getDefinition('handlebars.loader.filesystem');

        $config = $this->processConfiguration($configuration, $configs);
        // register user-configured paths
        foreach ($config['paths'] as $path => $namespace) {
            if (!$namespace) {
                $handlebarsFilesystemLoaderDefinition->addMethodCall('addPath', array($path));
            } else {
                $handlebarsFilesystemLoaderDefinition->addMethodCall('addPath', array($path, $namespace));
            }
        }
        $dir = $container->getParameter('kernel.root_dir').'/Resources/views';
        if (is_dir($dir)) {
            $handlebarsFilesystemLoaderDefinition->addMethodCall('addPath', array($dir));
        }
        $container->addResource(new FileExistenceResource($dir));

        // register bundles as Handlebars namespaces
        foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
            $dir = $container->getParameter('kernel.root_dir').'/Resources/'.$bundle.'/views';
            if (is_dir($dir)) {
                $handlebarsFilesystemLoaderDefinition->addMethodCall('addPath', array($dir));
            }
            $container->addResource(new FileExistenceResource($dir));

            $reflection = new \ReflectionClass($class);
            $dir = dirname($reflection->getFileName()).'/Resources/views';
            if (is_dir($dir)) {
                $handlebarsFilesystemLoaderDefinition->addMethodCall('addPath', array($dir));
            }
            $container->addResource(new FileExistenceResource($dir));
        }

        $container->getDefinition('handlebars')->replaceArgument(1, $config);
    }
}
