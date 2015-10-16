# Drush PHP

Php library for interacting with [Drush](https://github.com/drush-ops/drush) Drupal cli tool
 

## Installation **(with composer)** :

```
composer require ec-europa/phpdrush
```

## Usage

### Getting data

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

```
