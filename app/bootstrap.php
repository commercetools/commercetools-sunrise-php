<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise;

use Commercetools\Core\Client;
use Commercetools\Core\Config;
use Commercetools\Core\Request\Products\ProductProjectionBySlugGetRequest;
use Commercetools\Sunrise\Template\Adapter\HandlebarsAdapter;
use Commercetools\Sunrise\Template\TemplateService;
use Silex\Application;
use Commercetools\Core\Model\Common\Context;

require __DIR__.'/../vendor/autoload.php';

define('PROJECT_DIR', dirname(__DIR__));

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

$app['view'] = function () {
    return new TemplateService(new HandlebarsAdapter(PROJECT_DIR .'/output'));
};

$app->get('/', function() {
   return 'Sunrise Home';
});

$app->get('/catalog', function(Application $app) {
    $viewData = json_decode(file_get_contents(__DIR__ . '/../vendor/commercetools/sunrise-design/output/templates/pop.json'), true);
    $viewData['meta']['assetsPath'] = '/assets/';
    /**
     * @var callable $renderer
     */
    return $app['view']->render('product-overview', $viewData);
});

$app->get('/{slug}', function(Application $app, $slug) {
    $client = $app['client'];
    $request = ProductProjectionBySlugGetRequest::ofSlugAndContext($slug, $client->getConfig()->getContext());
    $response = $app['client']->execute($request);

    if ($response->isError() || is_null($response->toObject())) {
        $app->abort(404, "product $slug does not exist.");
    }

    $viewData = json_decode(file_get_contents(__DIR__ . '/../vendor/commercetools/sunrise-design/output/templates/pdp.json'), true);

    return $app['view']->render('product-detail', $viewData);
});

return $app;
