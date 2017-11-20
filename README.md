# Drush PHP

Php library for interacting with [Drush](https://github.com/drush-ops/drush) Drupal cli tool
 
## Warning

You shall not use this library directly in a webpage, since running one of those
methods will take minutes to finish. Instead, this class is made to be used
by command line tools, like deployment tools.

## Installation **(with composer)** :

```
composer install
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

## Tests

### Performing a PHP_CodeSniffer analysis

Before committing, ensure your code is clean by running either composer phpcs or bin/phpcs
```
./bin/phpcs -p --report=full --report=source --report=summary -s --colors
```

### Performing PHPUnit tests

Before committing, ensure there is no regression by running either composer phpunit or bin/phpunit

A drupal instance is needed to perform PHPUnit tests. It can be quickly install using drush:
```
./bin/drush dl drupal-7 --drupal-project-rename=drupal --yes
./bin/drush -r drupal site-install standard --account-name=admin --account-pass=admin --db-url=mysql://username:password@hostname/database --yes
```

