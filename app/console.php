#!/usr/bin/env php
<?php

namespace Commercetools\Sunrise;

use Commercetools\Sunrise\AppKernel;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;

set_time_limit(0);

/**
 * @var \Composer\Autoload\ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/AppKernel.php';
// auto-load annotations
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$input = new ArgvInput();
$env = $input->getParameterOption(['--env', '-e'], getenv('SYMFONY_ENV') ?: 'dev');
$debug = getenv('SYMFONY_DEBUG') !== '0' && !$input->hasParameterOption(['--no-debug', '']) && $env !== 'prod';
if ($debug) {
    Debug::enable();
}
$kernel = new AppKernel($env, $debug);
$application = new Application($kernel);
$application->run($input);
