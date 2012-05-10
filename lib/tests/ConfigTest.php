<?php

require_once(__DIR__ . '/../Configula/Config.php');
require_once(__DIR__ . '/../Configula/DriverInterface.php');
require_once(__DIR__ . '/../Configula/Drivers/Php.php');

class ConfigTest extends PHPUnit_Framework_TestCase {

  private $content_path;

  // --------------------------------------------------------------

  function setUp()
  {
    parent::setUp();

    $ds = DIRECTORY_SEPARATOR;
    $this->content_path = sys_get_temp_dir() . $ds . 'phpunit_configula_test_' . time();

    //Setup fake content directory
    mkdir($this->content_path);

    $php_good_code = '<?php
      $config = array();
      $config["a"] = "value";
      $config["b"] = array(1, 2, 3);
      $config["c"] = (object) array("d", "e", "f");
      /*EOF*/';

    $php_bad_code = '<?php
      $nuthin = "yep";
    ';

    file_put_contents($this->content_path . $ds . 'phpgood.php', $php_good_code);
    file_put_contents($this->content_path . $ds . 'phpbad.php',  $php_bad_code);
  }

  // --------------------------------------------------------------

  function tearDown()
  {    
    $ds = DIRECTORY_SEPARATOR;

    unlink($this->content_path . $ds . 'phpgood.php');
    unlink($this->content_path . $ds . 'phpbad.php');
    rmdir($this->content_path);

    parent::tearDown();
  } 

  // --------------------------------------------------------------

  public function testInstantiateAsObjectSucceeds() {

    $obj = new Configula\Config();
    $this->assertInstanceOf('Configula\Config', $obj);
  }

  // --------------------------------------------------------------

  public function testObjectUsesDefaultValuesWhenNoConfigDirSpecified() {

    $defaults = array(
      'a' => 'value',
      'b' => array(1, 2, 3),
      'c' => (object) array('d' => 'e', 'f' => 'g')
    );

    $obj = new Configula\Config(NULL, $defaults);

    $this->assertEquals('value', $obj->a);
    $this->assertEquals(1, $obj->b[0]);
    $this->assertEquals('e', $obj->c->d);
  }

  // --------------------------------------------------------------

  public function testObjectNonMagicInterfaceMethodWorks() {

    $defaults = array(
      'a' => 'value',
      'b' => array(1, 2, 3),
      'c' => (object) array('d' => 'e', 'f' => 'g')
    );

    $obj = new Configula\Config(NULL, $defaults);

    $this->assertEquals('value', $obj->get_item('a'));
    $this->assertEquals(array(1, 2, 3), $obj->get_item('b'));
    $this->assertEquals('e', $obj->get_item('c')->d);
  }

  // --------------------------------------------------------------

  public function testNonExistentValuesReturnsNull() {

    $defaults = array(
      'a' => 'value',
      'b' => array(1, 2, 3),
      'c' => (object) array('d' => 'e', 'f' => 'g')
    );

    $obj = new Configula\Config(NULL, $defaults);

    $this->assertEquals(NULL, $obj->non_existent);
    $this->assertEquals(NULL, $obj->get_item('doesnotexist'));
  }

  // --------------------------------------------------------------

  public function testParseConfigFileWorksForValidFile() {

    $filepath = $this->content_path . DIRECTORY_SEPARATOR . 'phpgood.php';

    $obj = new Configula\Config();
    $result = $obj->parse_config_file($filepath);
    

    $this->assertEquals('value', $result['a']);
    $this->assertEquals(1, $result['b'][0]);
  }

  // --------------------------------------------------------------

  public function testParseConfigFileReturnsEmptyArrayForInvalidFile() {

    $filepath = $this->content_path . DIRECTORY_SEPARATOR . 'phpbad.php';

    $obj = new Configula\Config();
    $result = $obj->parse_config_file($filepath);
    
    $this->assertEquals(array(), $result);
  }

  // --------------------------------------------------------------

  public function testParseConfigFileThrowsExceptionForUnreadableFile() {

    $filepath = $this->content_path . 'abc' . rand('1000', '9999') . '.php';

    try {
      $obj = new Configula\Config();
      $result = $obj->parse_config_file($filepath);
    } catch (Exception $e) {
      return;
    }

    $this->fail("Parse Config File should have thrown an exception for non-existent file: " . $filepath);

  }

  // --------------------------------------------------------------

  public function testInstantiateWithValidPathBuildsCorrectValues() {

    $obj = new Configula\Config($this->content_path);

    $this->assertEquals('value', $obj->a);
    $this->assertEquals(1, $obj->b[0]);
  }

  // --------------------------------------------------------------

  public function testInstantiateWithInvalidPathBuildsNoValues() {

    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpunit_test_nothing_' . time();
    mkdir($path);
    $obj = new Configula\Config($path);

    $this->assertEquals(NULL, $obj->a);
    $this->assertEquals(array(), $obj->get_item());

    rmdir($path);
  }

  // --------------------------------------------------------------

  public function testLocalConfigFileOverridesMainConfigFile() {
    
    $ds = DIRECTORY_SEPARATOR;
    $code = '<?php
      $config = array();
      $config["a"] = "newvalue";
      $config["c"] = (object) array("j", "k", "l");
      /*EOF*/';

    file_put_contents($this->content_path . $ds . 'phpgood.local.php', $code);

    $obj = new Configula\Config($this->content_path);

    $this->assertEquals('newvalue', $obj->a);
    $this->assertEquals((object) array('j', 'k', 'l'), $obj->c);
    $this->assertEquals(array(1, 2, 3), $obj->b);

    unlink($this->content_path . $ds . 'phpgood.local.php');
  }
}

/* EOF: PhpDriverTest.php */