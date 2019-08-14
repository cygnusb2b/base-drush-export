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

$dsn = getenv('MONGO_DSN');
$key = getenv('KEY');

if (!$dsn) {
    echo "\r\n\r\nERROR! You MUST specify the MongoDB server using the `MONGO_DSN` environment variable.\r\n\r\n";
    exit(1);
}

if (!$key) {
    echo "\r\n\r\nERROR! You MUST specify a valid configuration key using the `KEY` environment variable.\r\n\r\n";
    exit(1);
}
$dsn = false === stristr($dsn, 'mongodb://') ? sprintf('mongodb://%s', $dsn) : $dsn;

if ('nashvillepost' == $key) $class = 'Cygnus\\DrushExport\\ExportNVP';
if ('aw' == $key) $class = 'Cygnus\DrushExport\ExportD75AW';
if ('hp' == $key) $class = 'Cygnus\DrushExport\ExportD75AW';

$export = new $class($key, $dsn);
$export->execute();

__HALT_COMPILER();
