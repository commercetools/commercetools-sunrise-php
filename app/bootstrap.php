<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise;

use Commercetools\Core\Cache\CacheAdapterFactory;
use Commercetools\Core\Client;
use Commercetools\Sunrise\Controller\CartController;
use Commercetools\Sunrise\Controller\CatalogController;
use Commercetools\Sunrise\Model\Config;
use Commercetools\Sunrise\Model\Repository\CartRepository;
use Commercetools\Sunrise\Model\Repository\CategoryRepository;
use Commercetools\Sunrise\Model\Repository\ProductRepository;
use Commercetools\Sunrise\Model\Repository\ProductTypeRepository;
use Commercetools\Sunrise\Model\Repository\ShippingMethodRepository;
use Commercetools\Sunrise\Service\ClientFactory;
use Commercetools\Sunrise\Service\CookieSessionServiceProvider;
use Commercetools\Sunrise\Service\LocaleConverter;
use Commercetools\Sunrise\Template\Adapter\HandlebarsAdapter;
use Commercetools\Sunrise\Template\TemplateService;
use Silex\Application;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
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

$app['config'] = function () use ($app) {
    $cachePath = PROJECT_DIR . '/cache/config.php';

    $configCache = new ConfigCache($cachePath, CONFIG_DEBUG);
    if (!$configCache->isFresh()) {
        $locator = new FileLocator([
            PROJECT_DIR.'/app/config'
        ]);
        // fill this with an array of 'users.yml' file paths
        $yamlConfigFiles = [
            'app.yaml.dist',
            'app.yaml',
        ];

        $resources = array();

        $config = [];
        foreach ($yamlConfigFiles as $yamlConfigFile) {
            try {
                $fileName = $locator->locate($yamlConfigFile);
                // see the previous article "Loading resources" to
                // see where $delegatingLoader comes from
                // $delegatingLoader->load($yamlUserFile);
                $config = array_replace_recursive($config, Yaml::parse(file_get_contents($fileName)));
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
$app->register(new SessionServiceProvider());
//$app['session.storage.handler'] = function () use ($app) {
//    $encryptionKey = $app['config']['default.session.encryptionKey'];
//    $encryptionKeySalt = $app['config']['default.session.encryptionKeySalt'];
//    $enforceSecureCookie = $app['config']['default.session.enforceSecureCookie'];
//    if (getenv('SESSION_ENCRYPTION_KEY')) {
//        $encryptionKey = getenv('SESSION_ENCRYPTION_KEY');
//        $encryptionKeySalt = getenv('SESSION_ENCRYPTION_KEY_SALT');
//    }
//    \SecureClientSideSessionHandler::$cookieSecure = $enforceSecureCookie;
//    return new \SecureClientSideSessionHandler($encryptionKey, $encryptionKeySalt);
//};
// Register the monolog logging service
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => $app['config']['default.log.file'],
));

$app->register(
    new TranslationServiceProvider(),
    [
        'translator.cache_dir' => PROJECT_DIR . '/' . $app['config']['default.i18n.cache_dir'],
        'debug' => $app['config']['debug'],
        'domains' => $app['config']['default.i18n.namespaces']
    ]
);
$app['translator'] = $app->extend('translator', function(Translator $translator) use ($app) {
    $translator->addLoader('yaml', new YamlFileLoader());
    $paths = array_map(function ($path) { return realpath(PROJECT_DIR . '/' . $path);}, $app['config']['default.i18n.resourceDirs']);
    $locator = new FileLocator($paths);

    $languages = array_keys($app['languages']);
    foreach ($app['config']['default.i18n.namespace.namespaces'] as $namespace) {
        foreach ($languages as $language) {
            try {
                $files = $locator->locate($language . '/' . $namespace . '.yaml', null, false);
                if (is_array($files)) {
                    foreach ($files as $file) {
                        $translator->addResource('yaml', $file, $language, $namespace);
                    }
                } elseif ($files) {
                    $translator->addResource('yaml', $files, $language, $namespace);
                }
            } catch (\InvalidArgumentException $e) {}
        }
    }
    return $translator;
});

/**
 * Helper
 */
$app['cache'] = function () use ($app) {
    $factory = new CacheAdapterFactory();

    return $factory->get();
};
$app['template'] = function () use ($app) {
    return new TemplateService(
        new HandlebarsAdapter(
            PROJECT_DIR . '/' .$app['config']['default.templates.cache_dir'],
            $app['translator'],
            $app['config']['default.i18n.namespace.defaultNs'],
            $app['config']['default.i18n.interpolationPrefix'],
            $app['config']['default.i18n.interpolationSuffix']
        )
    );
};

$app->view(function ($result) use ($app) {
    list($page, $viewData) = $result;

    return $app['template']->render($page, $viewData);
});

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

$app['repository.product'] = function () use ($app) {
    return new ProductRepository(
        $app['config'],
        $app['cache'],
        $app['client']
    );
};
$app['repository.category'] = function () use ($app) {
    return new CategoryRepository(
        $app['config'],
        $app['cache'],
        $app['client']
    );
};
$app['repository.productType'] = function () use ($app) {
    return new ProductTypeRepository(
        $app['config'],
        $app['cache'],
        $app['client']
    );
};

$app['repository.cart'] = function () use ($app) {
    $locale = $app['locale.converter']->convert($app['locale']);
    return new CartRepository(
        $app['config'],
        $app['cache'],
        $app['client'],
        $app['repository.shippingMethod'],
        $locale
    );
};

$app['repository.shippingMethod'] = function () use ($app) {
    return new ShippingMethodRepository(
        $app['config'],
        $app['cache'],
        $app['client']
    );
};
/**
 * Catalog Controller
 */
$app['catalog.controller'] = function () use ($app) {
    $locale = $app['locale.converter']->convert($app['locale']);
    return new CatalogController(
        $app['client'],
        $locale,
        $app['url_generator'],
        $app['cache'],
        $app['translator'],
        $app['config'],
        $app['session'],
        $app['repository.category'],
        $app['repository.productType'],
        $app['repository.product']
    );
};
/**
 * Cart Controller
 */
$app['cart.controller'] = function () use ($app) {
    $locale = $app['locale.converter']->convert($app['locale']);
    return new CartController(
        $app['client'],
        $locale,
        $app['url_generator'],
        $app['cache'],
        $app['translator'],
        $app['config'],
        $app['session'],
        $app['repository.category'],
        $app['repository.productType'],
        $app['repository.cart']
    );
};

/**
 * Routes
 */
$app->get('/', 'catalog.controller:home');

$app->get('/{_locale}/', 'catalog.controller:home')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('home');
$app->get('/{_locale}/search/', 'catalog.controller:search')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('pop');
$app->get('/{_locale}/checkout', 'cart.controller:checkout')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('checkout');
$app->get('/{_locale}/checkout/signin', 'cart.controller:checkoutSignin')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('checkoutSignin');
$app->get('/{_locale}/checkout/shipping', 'cart.controller:checkoutShipping')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('checkoutShipping');
$app->get('/{_locale}/checkout/payment', 'cart.controller:checkoutPayment')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('checkoutPayment');
$app->get('/{_locale}/checkout/confirmation', 'cart.controller:checkoutConfirmation')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('checkoutConfirmation');
$app->post('/{_locale}/cart/add', 'cart.controller:add')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('cartAdd');
$app->post('/{_locale}/cart/delete', 'cart.controller:deleteLineItem')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('lineItemDelete');
$app->post('/{_locale}/cart/change', 'cart.controller:changeLineItem')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('lineItemChange');
$app->get('/{_locale}/cart', 'cart.controller:index')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('cart');

$app->get('/{_locale}/{category}/', 'catalog.controller:search')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('category');
$app->get('/{_locale}/{slug}.html', 'catalog.controller:detail')
    ->bind('pdp-master')
    ->assert('_locale', LOCALE_PATTERN);
$app->get('/{_locale}/{slug}/{sku}.html', 'catalog.controller:detail')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('pdp');

// location redirects for uri without locale information
$app->get('/search/', function(Application $app) {
    return $app->redirect('/'.$app['locale'].'/search/');
});
$app->get('/{slug}/{sku}.html', function(Application $app, $slug, $sku) {
    return $app->redirect('/'.$app['locale'].'/' . $slug . '/' . $sku . '.html');
});
$app->get('/{slug}.html', function(Application $app, $slug) {
    return $app->redirect('/'.$app['locale'].'/' . $slug . '.html');
});
$app->get('/{category}/', function(Application $app, $category) {
    return $app->redirect('/' . $app['locale'] . '/' . $category);
});

$app->error(function (NotFoundHttpException $e) use ($app) {
    $message = $app['translator']->trans('error.not_found');

    return new Response($message);
});

if (!$app['config']['debug']) {
    $app->error(function (\Exception $e) {
        return new Response('Bad things happen');
    });
}
return $app;
