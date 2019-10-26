<?php
require_once 'phar://export.phar/vendor/autoload.php';

define('DRUPAL_VERSION', function_exists('drush_core_status') ? drush_core_status('drupal-version')['drupal-version'] : null);

// $class = 'Cygnus\\DrushExport\\ExportD6';
// if (version_compare(DRUPAL_VERSION, '7.0') >= 0) {
//     if (version_compare(DRUPAL_VERSION, '7.5') >= 0) {
//         $class = 'Cygnus\\DrushExport\\ExportD75';
//     } else {
//         $class = 'Cygnus\\DrushExport\\ExportD7';
//     }
// }

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

$pmmi = ['aw', 'hp', 'oem', 'pw', 'pfw', 'lsl', 'sc'];
if (in_array($key, $pmmi)) $class = 'Cygnus\DrushExport\PMMI\BaseExport';
// if ('gp' == $key) $class = 'Cygnus\DrushExport\PMMI\GP'; // drupal 6 :|

$indm = ['id', 'mnet'];
if (in_array($key, $indm)) $class = 'Cygnus\DrushExport\INDM\BaseExport';

$informa = ['industryweek'];
if (in_array($key, $informa)) $class ='Cygnus\DrushExport\Informa\BaseExport';

if (!$class) throw new \InvalidArgumentException('Unable to find a valid import class.');

$export = new $class($key, $dsn);
$export->execute();

__HALT_COMPILER();
