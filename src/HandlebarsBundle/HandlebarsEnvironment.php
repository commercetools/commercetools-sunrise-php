<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */


namespace Commercetools\Sunrise\HandlebarsBundle;


use Commercetools\Sunrise\HandlebarsBundle\Cache\Filesystem;
use Commercetools\Sunrise\HandlebarsBundle\Loader\FilesystemLoader;
use LightnCandy\LightnCandy;

class HandlebarsEnvironment
{
    protected $options;
    protected $originalCache;

    /**
     * @var Filesystem
     */
    protected $cache;
    /**
     * @var FilesystemLoader
     */
    protected $loader;
    private $lastModifiedExtension = 0;

    protected $extensions = [];
    protected $autoReload;
    protected $debug;

    public function __construct(FilesystemLoader $loader, HandlebarsHelper $helper, $options = [])
    {
        $this->loader = $loader;
        $this->options = array_merge([
            'auto_reload' => null,
            'debug' => true,
            'flags' => LightnCandy::FLAG_BESTPERFORMANCE |
                LightnCandy::FLAG_ERROR_EXCEPTION |
                LightnCandy::FLAG_NAMEDARG |
                LightnCandy::FLAG_ADVARNAME |
                LightnCandy::FLAG_RUNTIMEPARTIAL |
                LightnCandy::FLAG_HANDLEBARSJS |
                LightnCandy::FLAG_ERROR_EXCEPTION,
            'basedir' => $loader->getPaths(),
            'fileext' => [
                '.hbs',
                '.handlebars'
            ],
        ], $options);
        $this->debug = (bool) $this->options['debug'];
        $this->autoReload = null === $this->options['auto_reload'] ? $this->debug : (bool) $this->options['auto_reload'];
        $this->setCache($this->options['cache']);
    }

    public function compile($name)
    {
        $source = $this->loader->getSource($name);
        $cacheKey = $this->getCacheFilename($name);

        $phpStr = '';
        try {
            $phpStr = LightnCandy::compile($source, $this->options);
        } catch (\Exception $e) {
            var_dump($e);
        }
        $this->cache->write($cacheKey, '<?php // ' . $name . PHP_EOL . $phpStr);

        return $phpStr;
    }

    public function render($name, array $context = [])
    {
        $renderer = $this->loadTemplate($name);

        return $renderer($context);
    }

    /**
     * Sets the current cache implementation.
     *
     * @param string|false $cache A Twig_CacheInterface implementation,
     *                                                an absolute path to the compiled templates,
     *                                                or false to disable cache
     */
    public function setCache($cache)
    {
        if (is_string($cache)) {
            $this->originalCache = $cache;
            $this->cache = new Filesystem($cache);
        } else {
            throw new \LogicException(sprintf('Cache can only be a string.'));
        }
    }

    public function getCacheFilename($name)
    {
        $key = $this->cache->generateKey($name);

        return !$key ? false : $key;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * @param $templateName
     * @return callable
     */
    public function loadTemplate($templateName)
    {
        $name = (string)$templateName;
        $cacheKey = $this->getCacheFilename($name);

        if (!$this->isAutoReload() && file_exists($cacheKey)) {
            return $this->cache->load($cacheKey);
        } else if ($this->isAutoReload() && $this->isTemplateFresh($name, $this->cache->getTimestamp($cacheKey))) {
            return $this->cache->load($cacheKey);
        }
        $this->compile($name);

        return $this->cache->load($cacheKey);
    }

    public function isTemplateFresh($name, $time)
    {
        return $this->loader->isFresh($name, $time);
    }

    /**
     * Checks if the auto_reload option is enabled.
     *
     * @return bool true if auto_reload is enabled, false otherwise
     */
    public function isAutoReload()
    {
        return $this->autoReload;
    }
}
