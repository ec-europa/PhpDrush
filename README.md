# Drush PHP

Php library for interacting with [Drush](https://github.com/drush-ops/drush) Drupal cli tool
 
## Warning

You shall not use this library directly in a webpage, since running one of those
methods will take minutes to finish. Instead, this class is made to be used
by command line tools, like deployment tools.

## Installation **(with composer)** :

```
composer require ec-europa/phpdrush
```

## Usage

```php
require 'vendor/autoload.php';

$drush = new \PhpDrush\PhpDrush( '/local/path/to/drush', '/local/path/to/site' );

// run updb :

$drush->updateDatabase();

// run registry rebuild (rr) :

$drush->registryRebuild();

// run full feature revert :

$drush->featuresRevert();

// run selective feature revert :

$drush->featuresRevert(
    ['feature1','feature2','feature3']
);

// enable maintenance mode :

$drush->setMaintenanceMode(true);

// clear all caches :

$drush->clearCache();

// evaluate php code in the drush context
$drush->ev('echo "Hello world!"');


```

## Usage Parallel:

Parallel mode allow to run, for a drupal master, several commands in parallel.
URI attribute can be used to choose the target subsite.
Alias @sites is not yet supported.

```php

require 'vendor/autoload.php';


$drushParall = new \PhpDrush\PhpDrush( '/local/path/to/drush', '/local/path/to/site' , NULL , TRUE);

// Enable Parallel
$drushParall->setParallelMode(TRUE);

// Select URI subsiteA
$drushParall->setUri("subsiteA");
// Set command for subsiteA
$drushParall->userLogin();
$drushParall->userLogin(NULL,'admin');

// Select URI subsiteB
$drushParall->setUri("subsiteB");
// Set command for subsiteB
$drushParall->userLogin();
$drushParall->userLogin(NULL,'admin');

// Select URI subsiteC
$drushParall->setUri("subsiteC");
// Set command for subsiteC
$subsiteCULI_id = $drushParall->userLogin();
$drushParall->userLogin(NULL,'admin');

// Run drush commands
$output = array();
$rc = array();
$maxProcess = 2;
$pollingInterval  = 1000;

$drushParall->runDrushParall($output,$rc,$maxProcess,$pollingInterval);

// Print output for ULI on subsiteC
if ($rc[$subsiteCULI_id] === 0){
  print ($output[$subsiteCULI_id]);
}
```
