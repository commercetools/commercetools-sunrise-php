<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise;

use Commercetools\Core\Cache\CacheAdapterFactory;
use Commercetools\Core\Client;
use Commercetools\Sunrise\Controller\CatalogController;
use Commercetools\Sunrise\Model\Config;
use Commercetools\Sunrise\Service\ClientFactory;
use Commercetools\Sunrise\Service\LocaleConverter;
use Commercetools\Sunrise\Template\Adapter\HandlebarsAdapter;
use Commercetools\Sunrise\Template\TemplateService;
use Silex\Application;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Yaml\Yaml;

require __DIR__.'/../vendor/autoload.php';

define('PROJECT_DIR', dirname(__DIR__));
define('CONFIG_DEBUG', true);

const LOCALE_PATTERN = '[a-z]{2}([_-][a-z]{2})?';

$app = new Application();

$app['locator'] = function () use ($app) {
    return new FileLocator([
        PROJECT_DIR.'/app/config'
    ]);
};
$app['config'] = function () use ($app) {
    $cachePath = PROJECT_DIR . '/cache/config.php';

    $configCache = new ConfigCache($cachePath, CONFIG_DEBUG);
    if (!$configCache->isFresh()) {
        // fill this with an array of 'users.yml' file paths
        $yamlConfigFiles = [
            'app.yaml.dist',
            'app.yaml',
        ];

        $resources = array();

        $config = [];
        foreach ($yamlConfigFiles as $yamlConfigFile) {
            try {
                $fileName = $app['locator']->locate($yamlConfigFile);
                // see the previous article "Loading resources" to
                // see where $delegatingLoader comes from
                // $delegatingLoader->load($yamlUserFile);
                $config = array_merge($config, Yaml::parse(file_get_contents($fileName)));
                $resources[] = new FileResource($yamlConfigFile);
            } catch (\InvalidArgumentException $e) {}
        }

        $config = new Config($config);
        // the code for the UserMatcher is generated elsewhere
        $code = sprintf(<<<EOF
<?php

use \Commercetools\Sunrise\Model\Config;

return new Config(%s);

EOF
            ,
            var_export($config->toArray(), true)
        );

        $configCache->write($code, $resources);

    } else {
        // you may want to require the cached code:
        $config = require $cachePath;
    }

    return $config;
};

$app['locale'] = $app['config']['default.locale'];
$app['country'] = $app['config']['default.country'];
$app['languages'] = function () use ($app) {
    $languages = $app['config']['default.languages'];
    $fallbackLanguages = $app['config']['default.fallback_languages'];
    $fallbacks = [];
    foreach ($languages as $language) {
        $fallbacks[$language] = [$language];
        if (isset($fallbackLanguages[$language])) {
            $fallbacks[$language] = array_merge($fallbacks[$language], $fallbackLanguages[$language]);
        }
    }
    return $fallbacks;
};

/**
 * Provider
 */
$app->register(new LocaleServiceProvider());
$app->register(new ServiceControllerServiceProvider());
// Register the monolog logging service
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

$app->register(
    new TranslationServiceProvider(),
    [
        'translator.cache_dir' => $app['config']['default.i18n.cache_dir'],
        'debug' => $app['config']['debug']
    ]
);
$app['translator'] = $app->extend('translator', function(Translator $translator, Application $app) {
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', PROJECT_DIR.'/app/locales/en.yaml', 'en');
        $translator->addResource('yaml', PROJECT_DIR.'/app/locales/de.yaml', 'de');
        return $translator;
});

/**
 * Helper
 */
$app['cache'] = function () use ($app) {
    $factory = new CacheAdapterFactory();

    return $factory->get();
};
$app['view'] = function () {
    return new TemplateService(new HandlebarsAdapter(PROJECT_DIR .'/cache/templates'));
};
$app['locale.converter'] = function () use ($app) {
    return new LocaleConverter($app);
};
$app['client'] = function () use ($app) {
    $locale = $app['locale.converter']->convert($app['locale']);
    return ClientFactory::build(
        $locale,
        $app['config']['commercetools'],
        $app['languages'],
        $app['cache'],
        ($app['config']['debug'] ? $app['logger'] : null)
    );
};

/**
 * Controller
 */
$app['catalog.controller'] = function () use ($app) {
    $locale = $app['locale.converter']->convert($app['locale']);
    return new CatalogController(
        $app['client'],
        $locale,
        $app['view'],
        $app['url_generator'],
        $app['translator'],
        $app['config']['sunrise']
    );
};

/**
 * Routes
 */
$app->get('/', 'catalog.controller:home');

$app->get('/{_locale}', 'catalog.controller:home')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('home');
$app->get('/{_locale}/search', 'catalog.controller:search')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('pop');
$app->get('/{_locale}/{slug}.html', 'catalog.controller:detail')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('pdp');
$app->get('/{_locale}/{category1}', 'catalog.controller:search')
    ->assert('_locale', LOCALE_PATTERN);

// location redirects for trailing slashes
$app->get('/{_locale}/', function(Application $app) {
    return $app->redirect('/'.$app['locale']);
})->assert('_locale', LOCALE_PATTERN);
$app->get('/{_locale}/search/', function(Application $app) {
    return $app->redirect('/'. $app['locale'] . '/search');
})->assert('_locale', LOCALE_PATTERN);
$app->get('/{_locale}/{category}/', function($category, Application $app) {
    return $app->redirect('/'. $app['locale'] . '/' . $category);
})->assert('_locale', LOCALE_PATTERN);

// location redirects for uri without locale information
$app->get('/search', function(Application $app) {
    return $app->redirect('/'.$app['locale'].'/search');
});
$app->get('/{slug}.html', function(Application $app, $slug) {
    return $app->redirect('/'.$app['locale'].'/' . $slug . '.html');
});
$app->get('/{category}', function(Application $app, $category) {
    return $app->redirect('/' . $app['locale'] . '/' . $category);
});

$app->error(function (NotFoundHttpException $e) use ($app) {
    $message = $app['translator']->trans('error.not_found');

    return new Response($message);
});
return $app;
