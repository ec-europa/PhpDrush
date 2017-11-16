<?php

namespace EC\Tests\Lib;

class PhpDrushTest extends \PHPUnit_Framework_TestCase
{

  public static $drush = null;

  public static function setUpBeforeClass()
  {
    self::$drush = new \PhpDrush\PhpDrush(__DIR__ . '/../../bin/drush', __DIR__ . '/../../drupal/sites/default/');
  }
    
  public static function tearDownAfterClass()
  {
    self::$drush->sqlQuery("DELETE FROM users where name='TestUser'");
  }
    
  public function testUser()
  {
      
    self::$drush->userAdd("TestUser");
    $return = self::$drush->sqlQuery("select name from users ORDER BY uid DESC LIMIT 1;");
    $this->assertEquals("TestUser", $return[0]);
      
    $regEx = "#^([a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|" .
    "[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))$#";
    $this->assertRegExp($regEx, self::$drush->userLogin("TestUser"));
  }
  
  public function testMaintenanceMode()
  {
    
    self::$drush->setMaintenanceMode(1);
    $this->assertEquals('1', self::$drush->getVariable('maintenance_mode'));

    self::$drush->setMaintenanceMode(0);
    $this->assertEquals('0', self::$drush->getVariable('maintenance_mode'));
  }
  
  public function testModuleActivation()
  {
    
    self::$drush->enableModules(array('contact'));
    $return = self::$drush->sqlQuery("select status from system where name='contact';");
    $this->assertEquals('1', $return[0]);
    
    self::$drush->disableModules(array('contact'));
    $return = self::$drush->sqlQuery("select status from system where name='contact';");
    $this->assertEquals('0', $return[0]);
  }
}
