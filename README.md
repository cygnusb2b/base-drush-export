# base-drush-export
A PHAR project to export data to base-platform format from Drupal 6/7

## Requirements
This command is meant to be used within in existing Drupal install, and relies heavily on Drush. You must have both Drush and a working Drupal install available to use. You also need a MongoDB server available.

## Build
From project root, execute `php -d phar.readonly=0 compile`. The compiled PHAR archive will be output into the `build/` directory.

## Usage

From `DRUPAL_ROOT` directory, execute `drush scr /path/to/build/export.phar [MongoDB DSN] [configKey]`
