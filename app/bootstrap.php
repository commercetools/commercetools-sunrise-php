<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise;

use Symfony\Component\HttpFoundation\Request;

define('PROJECT_DIR', dirname(__DIR__));

require __DIR__.'/autoload.php';
include_once __DIR__.'/../var/bootstrap.php.cache';

$kernel = new \AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new \AppCache($kernel);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
