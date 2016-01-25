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
use Commercetools\Sunrise\Service\LocaleConverter;
use Commercetools\Sunrise\Template\Adapter\HandlebarsAdapter;
use Commercetools\Sunrise\Template\TemplateService;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Yaml\Yaml;

define('PROJECT_DIR', dirname(__DIR__));

require __DIR__.'/autoload.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
