<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Service;

use Commercetools\Core\Cache\CacheAdapterInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Model\Common\Context;
use Psr\Log\LoggerInterface;

class ClientFactory
{
    private $clientCredentials;
    private $fallbackLanguages;
    private $cache;
    private $logger;

    /**
     * ClientFactory constructor.
     * @param $clientCredentials
     * @param $fallbackLanguages
     * @param $cache
     * @param $logger
     */
    public function __construct(
        $clientCredentials,
        $fallbackLanguages,
        CacheAdapterInterface $cache,
        LoggerInterface $logger
    ) {
        $this->clientCredentials = $clientCredentials;
        $this->fallbackLanguages = $fallbackLanguages;
        $this->cache = $cache;
        $this->logger = $logger;
    }


    /**
     * @param $locale
     * @param $clientCredentials
     * @param $fallbackLanguages
     * @return static
     */
    public function build(
        $locale,
        $clientCredentials = null,
        $fallbackLanguages = null
    ) {
        if (is_null($clientCredentials)) {
            $clientCredentials = $this->clientCredentials;
        }
        if (is_null($fallbackLanguages)) {
            $fallbackLanguages = $this->fallbackLanguages;
        }
        $language = \Locale::getPrimaryLanguage($locale);
        $languages = array_merge([$language], $fallbackLanguages[$language]);
        $context = Context::of()->setLanguages($languages)->setGraceful(true)->setLocale($locale);
        if (getenv('SPHERE_CLIENT_ID')) {
            $config = [
                'client_id' => getenv('SPHERE_CLIENT_ID'),
                'client_secret' => getenv('SPHERE_CLIENT_SECRET'),
                'project' => getenv('SPHERE_PROJECT')
            ];
        } else {
            $config = $clientCredentials;
        }
        $config = Config::fromArray($config)->setContext($context);

        if (is_null($this->logger)) {
            return Client::ofConfigAndCache($config, $this->cache);
        }
        return Client::ofConfigCacheAndLogger($config, $this->cache, $this->logger);
    }
}
