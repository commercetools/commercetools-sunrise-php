<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';
require __DIR__ . '/AppKernel.php';
require __DIR__ . '/AppCache.php';
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
