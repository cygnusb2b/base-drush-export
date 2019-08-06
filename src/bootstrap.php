<?php
require_once 'phar://export.phar/vendor/autoload.php';
// require_once 'phar://export.phar/src/Cygnus/DrushExport/AbstractExport.php';
// require_once 'phar://export.phar/src/Cygnus/DrushExport/ExportD6.php';
// require_once 'phar://export.phar/src/Cygnus/DrushExport/ExportNVP.php';
// require_once 'phar://export.phar/src/Cygnus/DrushExport/ExportD7.php';
// require_once 'phar://export.phar/src/Cygnus/DrushExport/ExportD75.php';

define('DRUPAL_VERSION', function_exists('drush_core_status') ? drush_core_status('drupal-version')['drupal-version'] : null);

$class = 'Cygnus\\DrushExport\\ExportD6';
if (version_compare(DRUPAL_VERSION, '7.0') >= 0) {
    if (version_compare(DRUPAL_VERSION, '7.5') >= 0) {
        $class = 'Cygnus\\DrushExport\\ExportD75';
    } else {
        $class = 'Cygnus\\DrushExport\\ExportD7';
    }
}

ini_set('memory_limit', -1);
set_time_limit(0);

global $argv;

if (!isset($argv[5])) {
    echo "\r\n\r\nERROR! You MUST specify a MongoDB server as an argument.\r\n\r\n";
    exit(1);
}
$dsn = sprintf('mongodb://%s', $argv[5]);

if (!isset($argv[6])) {
    echo "\r\n\r\nERROR! You MUST specify a valid configuration key as an argument.\r\n\r\n";
    exit(1);
}
$key = $argv[6];
if ('nashvillepost' == $key) $class = 'cygnus\\DrushExport\\ExportNVP';
if ('aw' == $key) $class = 'Cygnus\DrushExport\ExportD75AW';

$export = new $class($key, $dsn);
$export->execute();

__HALT_COMPILER();
