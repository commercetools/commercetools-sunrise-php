<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Asset;

use Commercetools\Sunrise\AppBundle\Model\Config;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;

class AssetCache
{
    private $cache;
    private $assets;

    public function __construct($config, $file, $debug)
    {
        if (is_array($config)) {
            $config = new Config($config);
        }
        $this->cache = new ConfigCache($file . '/assetsCache.php', $debug);

        if (!$this->cache->isFresh()) {
            $assetsFile = $config->get('sunrise.assetsCache');
            $resources = [
                new FileResource($assetsFile)
            ];
            $assets = json_decode(file_get_contents($assetsFile), true);

            $code = '<?php' . PHP_EOL . 'return ' . var_export($assets, true) . ';';
            $this->cache->write($code, $resources);
        }
    }

    public function getFile($file)
    {
        $this->loadAssetsFromCache();

        return isset($this->assets[$file]) ? $this->assets[$file] : $file;
    }

    public function loadAssetsFromCache()
    {
        if (is_null($this->assets)) {
            $cacheFile = $this->cache->getPath();
            $this->assets = @include $cacheFile;
        }

        return $this->assets;
    }
}
