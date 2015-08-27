<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Core\Request\Products\ProductProjectionSearchRequest;
use Commercetools\Sunrise\Template\Adapter\HandlebarsAdapter;
use Commercetools\Sunrise\Template\TemplateService;
use Silex\Application;
use Commercetools\Core\Model\Common\Context;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

require __DIR__.'/../vendor/autoload.php';

define('PROJECT_DIR', dirname(__DIR__));
const DEFAULT_LANGUAGE = 'de';

$app = new Application();

$app['client'] = function () {
    $context = Context::of()->setLanguages(['en'])->setGraceful(true);
    if (file_exists(__DIR__ .'/myapp.ini')) {
        $appConfig = parse_ini_file(__DIR__ .'/myapp.ini', true);
        $config = $appConfig['commercetools'];
    } else {
        $config = Config::fromArray([
            'client_id' => $_SERVER['SPHERE_CLIENT_ID'],
            'client_secret' => $_SERVER['SPHERE_CLIENT_SECRET'],
            'project' => $_SERVER['SPHERE_PROJECT']
        ]);
    }
    $config = Config::fromArray($config)->setContext($context);

    return Client::ofConfig($config);
};

$app['languages'] = function () {
    return ['de' => ['de', 'en'], 'en' => ['en']];
};
$app['view'] = function () {
    return new TemplateService(new HandlebarsAdapter(PROJECT_DIR .'/output'));
};

$app->get('/', 'Commercetools\Sunrise\Controller\CatalogController::home')->value('lang', DEFAULT_LANGUAGE);
$app->get('/search', function(Application $app) {
    return $app->redirect('/'.DEFAULT_LANGUAGE.'/search');
});
$app->get('/{slug}.html', function(Application $app, $slug) {
    return $app->redirect('/'.DEFAULT_LANGUAGE.'/' . $slug . '.html');
});

$app->get('/{lang}/search', 'Commercetools\Sunrise\Controller\CatalogController::search');
$app->get('/{lang}/{slug}.html', 'Commercetools\Sunrise\Controller\CatalogController::detail');
$app->error(function (NotFoundHttpException $e) {
    $message = 'The requested page could not be found.';

    return new Response($message);
});
return $app;
