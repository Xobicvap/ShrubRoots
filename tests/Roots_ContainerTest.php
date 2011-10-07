<?php

// testing autoload method; path will probably need to be changed
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'testingautoload.php';

class Roots_ContainerTest extends PHPUnit_Framework_TestCase
{
    protected $object;
    
    protected function setUp() {
        spl_autoload_register('autoloadForTesting');
        $builder = new Mock_Builder;
        $this->object = new Roots_Container($builder);
    }
    
    public function testRetrieveItem() {
        $obj_name = 'baf!';
        $obj_inst = 'zaf!';
        $result = $this->object->retrieveItem($obj_name);
        $this->assertSame($obj_inst, $result);
    }
    
    public function testRetrieveItemBadName() {
        $obj_name = 'zax!';
        $message = "Object zax! not present in container.";
        $this->setExpectedException('Exception', $message);
        
        $result = $this->object->retrieveItem($obj_name);
    }
    
    public function testGetBuiltItems() {
        $result = $this->object->getBuiltItems();
        
        $arrtest = is_array($result);
        $this->assertTrue($arrtest);
    }
    
    public function testRetrieveFactoryBuiltObject() {
        $result = $this->object->retrieveFactoryBuiltObject('baf');
        $this->assertFalse($result);
        
        
    }
    
}
?>
