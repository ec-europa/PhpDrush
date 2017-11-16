<?php

namespace EC\Tests\Lib;

class MaintenanceModeTest extends \PHPUnit_Framework_TestCase
{

 public static $drush = null;

    public static function setUpBeforeClass()
    {
        self::$drush = new \PhpDrush\PhpDrush(__DIR__ . '/../../bin/drush', __DIR__ . '/../../drupal/sites/default/' );
    }
  public function testSetMaintenanceMode(){
    fwrite(STDOUT, __METHOD__ . "\n");
    
    self::$drush->setMaintenanceMode(1);
    $this->assertEquals('1', self::$drush->getVariable('maintenance_mode'));

    self::$drush->setMaintenanceMode(0);
    $this->assertEquals('0', self::$drush->getVariable('maintenance_mode'));
  }
}
