<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Service;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Model\Common\Context;
use Psr\Log\LoggerInterface;

class ClientFactory
{
    /**
     * @param $locale
     * @param $clientCredentials
     * @param $fallbackLanguages
     * @param LoggerInterface $logger
     * @param $cache
     * @return Client
     */
    public function build(
        $locale,
        $clientCredentials = null,
        $fallbackLanguages = null,
        $cache = null,
        LoggerInterface $logger = null
    ) {
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

        if (is_null($logger)) {
            return Client::ofConfigAndCache($config, $cache);
        }
        return Client::ofConfigCacheAndLogger($config, $cache, $logger);
    }
}
