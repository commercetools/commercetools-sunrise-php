<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

$startTime = microtime(true);
$app = require __DIR__.'/../app/bootstrap.php';
$app->run();
var_dump(microtime(true) - $startTime);
