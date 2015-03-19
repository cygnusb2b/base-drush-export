<?php
require_once 'phar://export.phar/Cygnus/DrushExport/AbstractExport.php';
require_once 'phar://export.phar/Cygnus/DrushExport/ExportD6.php';
require_once 'phar://export.phar/Cygnus/DrushExport/ExportD7.php';

define('DRUPAL_VERSION', drush_core_status('drupal-version')['drupal-version']);

$class = 'Cygnus\\DrushExport\\ExportD6';
if (version_compare(DRUPAL_VERSION, '7.0') >= 0) {
    $class = 'Cygnus\\DrushExport\\ExportD7';
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


$export = new $class($key, $dsn);
$export->execute();

__HALT_COMPILER();
