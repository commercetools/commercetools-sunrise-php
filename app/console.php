#!/usr/bin/env php
<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

use Commercetools\Sunrise\Console\Command\Hello;
use Commercetools\Sunrise\Console\Command\CompileTemplates;
use Cilex\Provider\Console\ConsoleServiceProvider;

set_time_limit(0);

$app = require_once __DIR__.'/bootstrap.php';

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'Sunrise',
    'console.version'           => '0.1.0',
    'console.project_directory' => PROJECT_DIR
));

$application = $app['console'];
$application->add(new Hello());
$application->add(new CompileTemplates());
$application->run();

?>
