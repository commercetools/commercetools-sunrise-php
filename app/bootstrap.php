<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise;

use Commercetools\Core\Cache\CacheAdapterFactory;
use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Sunrise\Template\Adapter\HandlebarsAdapter;
use Commercetools\Sunrise\Template\TemplateService;
use Silex\Application;
use Commercetools\Core\Model\Common\Context;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

require __DIR__.'/../vendor/autoload.php';

define('PROJECT_DIR', dirname(__DIR__));
const DEFAULT_LOCALE = 'de-de';
const LOCALE_PATTERN = '[a-z]{2}-[a-z]{2}';

$app = new Application();
$app->register(new LocaleServiceProvider());

// Register the monolog logging service
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

$app['locale'] = DEFAULT_LOCALE;

$app['client'] = function () use ($app) {
    $language = \Locale::getPrimaryLanguage($app['locale']);
    $languages = $app['languages'][$language];
    $context = Context::of()->setLanguages($languages)->setGraceful(true)->setLocale('de')->setLocale($app['locale']);
    if (file_exists(__DIR__ .'/myapp.ini')) {
        $appConfig = parse_ini_file(__DIR__ .'/myapp.ini', true);
        $config = $appConfig['commercetools'];
    } else {
        $config = [
            'client_id' => $_SERVER['SPHERE_CLIENT_ID'],
            'client_secret' => $_SERVER['SPHERE_CLIENT_SECRET'],
            'project' => $_SERVER['SPHERE_PROJECT']
        ];
    }
    $config = Config::fromArray($config)->setContext($context);

    return Client::ofConfig($config);
};
$app['cache'] = function () use ($app) {
    $factory = new CacheAdapterFactory();

    return $factory->get();
};

$app->register(new TranslationServiceProvider(), ['translator.cache_dir' => PROJECT_DIR . '/cache/translation']);
$app['translator'] = $app->extend('translator', function(Translator $translator, Application $app) {
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', PROJECT_DIR.'/app/locales/en.yaml', 'en');
        $translator->addResource('yaml', PROJECT_DIR.'/app/locales/de.yaml', 'de');
        return $translator;
});
$app['languages'] = function () {
    return ['de' => ['de', 'en'], 'en' => ['en']];
};

$app['view'] = function () {
    return new TemplateService(new HandlebarsAdapter(PROJECT_DIR .'/cache/templates'));
};

$app->get('/', 'Commercetools\Sunrise\Controller\CatalogController::home')->value('lang', DEFAULT_LOCALE);

$app->get('/{_locale}', 'Commercetools\Sunrise\Controller\CatalogController::home')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('home');
$app->get('/{_locale}/search', 'Commercetools\Sunrise\Controller\CatalogController::search')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('pop');
$app->get('/{_locale}/{slug}.html', 'Commercetools\Sunrise\Controller\CatalogController::detail')
    ->assert('_locale', LOCALE_PATTERN)
    ->bind('pdp');
$app->get('/{_locale}/{category1}', 'Commercetools\Sunrise\Controller\CatalogController::search')
    ->assert('_locale', LOCALE_PATTERN);

// location redirects for trailing slashes
$app->get('/{_locale}/', function(Application $app) {
    return $app->redirect('/'.$app['locale']);
})->assert('_locale', LOCALE_PATTERN);
$app->get('/{_locale}/search/', function($category1, $category2, Application $app) {
    return $app->redirect('/'. $app['locale'] . '/search');
})->assert('_locale', LOCALE_PATTERN);
$app->get('/{_locale}/{category1}/', function($category1, Application $app) {
    return $app->redirect('/'. $app['locale'] . '/' . $category1);
})->assert('_locale', LOCALE_PATTERN);

// location redirects for uri without locale information
$app->get('/search', function(Application $app) {
    return $app->redirect('/'.DEFAULT_LOCALE.'/search');
});
$app->get('/{slug}.html', function(Application $app, $slug) {
    return $app->redirect('/'.DEFAULT_LOCALE.'/' . $slug . '.html');
});
$app->get('/{category1}', function(Application $app, $category1) {
    return $app->redirect('/' . DEFAULT_LOCALE . '/' . $category1);
});

$app->error(function (NotFoundHttpException $e) use ($app) {
    $message = $app['translator']->trans('error.not_found');

    return new Response($message);
});
return $app;
