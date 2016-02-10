<?php
/**
 * @author jayS-de <jens.schulze@commercetools.de>
 */

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

require __DIR__ . '/../app/bootstrap.dev.php';
