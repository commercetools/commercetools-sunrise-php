<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\Service;


use Silex\Application;

class LocaleConverter
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function convert($locale)
    {
        $parts = \Locale::parseLocale($locale);
        if (!isset($parts['region'])) {
            $parts['region'] = $this->app['country'];
        }
        $locale = \Locale::canonicalize(\Locale::composeLocale($parts));

        return $locale;
    }
}
