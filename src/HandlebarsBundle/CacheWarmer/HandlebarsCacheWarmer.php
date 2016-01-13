<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\HandlebarsBundle\CacheWarmer;


use Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class HandlebarsCacheWarmer implements CacheWarmerInterface
{
    protected $container;
    protected $warmer;
    protected $finder;

    /**
     * Constructor.
     *
     * @param ContainerInterface      $container The dependency injection container
     * @param TemplateFinderInterface $finder    The template paths cache warmer
     */
    public function __construct(ContainerInterface $container, TemplateFinderInterface $finder)
    {
        // We don't inject the HandlebarsEngine directly as it depends on the
        // template locator (via the loader) which might be a cached one.
        // The cached template locator is available once the TemplatePathsCacheWarmer
        // has been warmed up
        $this->container = $container;
        $this->finder = $finder;
    }
    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir)
    {
        $engine = $this->container->get('handlebars');
        $logger = $this->container->has('logger') ? $this->container->get('logger') : null;
        foreach ($this->finder->findAllTemplates() as $template) {
            if (!in_array($template->get('engine'), ['hbs', 'handlebars'])) {
                continue;
            }
            try {
                $engine->compile($template);
            } catch (\Exception $e) {
                // problem during compilation, log it and give up
                if ($logger) {
                    $logger->warn(sprintf('Failed to compile Handlebars template "%s": "%s"', (string) $template, $e->getMessage()));
                }
            }
        }
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * @return Boolean always true
     */
    public function isOptional()
    {
        return true;
    }
}
