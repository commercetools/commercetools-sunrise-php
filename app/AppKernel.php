<?php

use Commercetools\Sunrise\AppBundle\AppBundle;
use Commercetools\Symfony\CtpBundle\CtpBundle;
use JaySDe\HandlebarsBundle\HandlebarsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Sensio\Bundle\DistributionBundle\SensioDistributionBundle;
use Symfony\Bundle\AsseticBundle\AsseticBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new HandlebarsBundle(),
            new MonologBundle(),
            new SensioFrameworkExtraBundle(),
            new AppBundle(),
            new CtpBundle(),
            new AsseticBundle(),
            new NelmioSecurityBundle(),
        ];
        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new DebugBundle();
            $bundles[] = new WebProfilerBundle();
            $bundles[] = new SensioDistributionBundle();
            $bundles[] = new SensioGeneratorBundle();
        }
        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }
}
