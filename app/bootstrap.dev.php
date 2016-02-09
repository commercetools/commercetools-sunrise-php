<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise;

use Symfony\Component\HttpFoundation\Request;

define('PROJECT_DIR', dirname(__DIR__));

require __DIR__.'/autoload.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
