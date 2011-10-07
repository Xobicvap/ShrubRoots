<?php
/**
 *  ShrubRoots Dependency Injection Container
 *  Copyright (C) 2011 Rusty Hamilton (rusty@shrub3.net)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// testing autoload method; path will probably need to be changed
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'testingautoload.php';

/** \class Roots_ObjectDemapperTest
 * \brief Tests behavior of the Roots_ObjectDemapper class.
 * 
 * Ensures that all means of building objects either perform as expected
 * or fail properly if given improper input. */
class Roots_ObjectDemapperTest extends PHPUnit_Framework_TestCase {
    
    /** Roots_ObjectDemapper instance.*/
    protected $object;
    
    /** Container object. Not really needed, but here anyway. */
    protected $cont;

    /** \brief Test fixture.
     * 
     * Fixture for testing; registers autoload, loads a mock container
     * and builds an object demapper instance. */
    protected function setUp() {
        spl_autoload_register('autoloadForTesting');
        $cont = new Mock_Container;
        $this->object = new Roots_ObjectDemapper($cont);
    }

    /** \brief Test fixture destructor.
     * 
     * Unsets test fixture objects and unregisters testing autoload. */
    protected function tearDown() {
        spl_autoload_unregister('autoloadForTesting');
        unset($this->object);              
    }
    
    /** \brief Tests object demapping of simple object maps.
     * 
     * Demaps a list of two simple, constructor-less objects. */
    public function testDemapSimpleObjectMap() {
        $test = array('A', 'B');       
        
        $result = $this->object->demap($test);
        $this->assertArrayHasKey('A', $result);
        $this->assertArrayHasKey('B', $result);
        $this->assertInstanceOf('A', $result['A']);
        $this->assertInstanceOf('B', $result['B']);
    }

    /** \brief Tests for proper handling of invalid map arrays.
     * 
     * Tests to make sure invalid values given for dependencies
     * in map arrays are handled properly. This shouldn't happen,
     * technically, but you never know. */
    public function testDemapInvalidArrayValue() {
        $test = array('A' => 5);
        $invalid_arr_mess = "Object A has an invalid dependency ".
                       "represented by 5. Please correct the ".
                       "error in your object mapping.";
        $this->setExpectedException('Exception', $invalid_arr_mess);
        
        $result = $this->object->demap($test);
    }
    
    /** \brief Tests to ensure (user) errors in dependency mappings are
     *         caught and handled properly.
     * 
     * Users may forget that a particular object in their mapping has
     * non-optional parameters in its constructor. This method tests to
     * make sure this sort of error is caught and handled properly. */
    public function testDemapUninstantiable() {
        $test = array('D');
        $uninst_mess = "Object D requires 2 parameters.";
        
        $this->setExpectedException('Exception', $uninst_mess);
        
        $result = $this->object->demap($test);
    }
    
    /** \brief Tests proper handling of mapping errors.
     * 
     * This is another case that really shouldn't happen, but
     * if somehow the mapping becomes garbled and an array is passed
     * to the wrong place, the ObjectDemapper should throw an exception
     * and tell you where it happened. This method tests to ensure this
     * is so. */
    public function testDemapIncorrectRead() {
        $inc_read_mess = 
                       "You have an error in your object mapping. ".
                       "ShrubRoots is reading the line containing ".
                       "object A incorrectly.";
        $this->setExpectedException('Exception', $inc_read_mess);
        
        $test = array(array('A'));
        $result = $this->object->demap($test);
    }
    
    /** \brief Tests a complex dependency situation, in which there exists
     *         one dependency per line. (i.e. Object1: [Dep1, Dep2]).
     * 
     * Tests to ensure objects with constructors can be handled, and that
     * previously created objects can indeed be reused to satisfy said
     * dependencies. */
    public function testDemapComplexSingleDep() {
        $test = array('A', 'B', 
                'D' => array('A', 'B'),
                'C' => array('A', 'D'));
        
        $result = $this->object->demap($test);
        
        $this->assertArrayHasKey('D', $result);
        $this->assertArrayHasKey('C', $result);
        $this->assertInstanceOf('D', $result['D']);
        $this->assertInstanceOf('C', $result['C']);
        
        $d_dump = $result['D']->dump();
        $c_dump = $result['C']->dump();
        
        $this->assertInstanceOf('A', $d_dump[0]);
        $this->assertInstanceOf('B', $d_dump[1]);
        $this->assertInstanceOf('A', $c_dump[0]);
        $this->assertInstanceOf('D', $c_dump[1]);
        
    }
    
    /** \brief Tests to ensure complex, multi-line dependencies work properly.
     * 
     * Tests a complex, nested dependency situation, ensuring that objects
     * are built as directed and contain the expected objects as dependencies.
     */
    public function testDemapComplexDeps() {
        $test = array('A', 'B',
                      'C' => array('A',
                                   'D' => array('A', 'B')));
        $result = $this->object->demap($test);
        
        $this->assertArrayHasKey('C', $result);
        $this->assertInstanceOf('C', $result['C']);
        
        $c_dump = $result['C']->dump();
        $this->assertInstanceOf('D', $c_dump[1]);
    }
    
    /** \brief Tests to ensure object cloning works.
     * 
     * Rather complicated logic here; we set up a new ObjectDemapper
     * with a clone list specifying that the D object should contain a 
     * clone of object A (which has already been instantiated for the
     * C object's A object dependency). We change the value of the D
     * object's A object, and expect that the (now separate) C object's
     * A object's value will be different from the D object's A object.
     * If cloning had failed, the instance of A injected into both C and D
     * will be the same, and the test will fail.
     * 
     * If you can think of a better or clearer way to explain this,
     * feel free to help out. ^_^
     */
    public function testCloning() {
        // we're going to rebuild the ObjectDemapper with a clone list
        // this time, so destroy the existing instance
        unset($this->object);
        
        // set up the clone list
        $clist = array('A' => array('D'));
        
        // rebuild the ObjectDemapper
        $cont = new Mock_Container;
        $this->object = new Roots_ObjectDemapper($cont, $clist);
        
        // create a dependency mapping
        $test = array('C' => array('A', 'D' => array('A', 'B')));
        
        // ensure dependency mapping works
        $result = $this->object->demap($test);
        $this->assertArrayHasKey('C', $result);
        
        // get the D object from the C object
        $c_val = $result['C']->dump();
        $dcopy = $c_val[1];
        
        // ensure D is actually a D object
        $this->assertInstanceOf('D', $dcopy);
        
        // change the value of field A in the A object in the D object
        // in the C object :D
        $result['C']->changeD();
        $afromc = $result['C']->showD();
        
        
        $acopy = $c_val[0];
        $this->assertInstanceOf('A', $acopy);

        $afroma = $acopy->getFoob();
        $this->assertThat(
                $afroma,
                $this->logicalNot(
                        $this->equalTo($afromc)));
        
    }
    
    /** \brief Test to make sure arrays of objects work as expected.
     * 
     * Builds a mapping specifying an array of objects, and ensures its
     * resulting keys and values are correct. */    
    public function testDemapArrayofObjects() {
        $test = array('obj_array' => array('A', 'B'));
       
        $result = $this->object->demap($test);
        
        $arrtest = is_array($result);
        
        $this->assertTrue($arrtest);
        $this->assertArrayHasKey('obj_array', $result);
        $this->assertArrayHasKey('A', $result['obj_array']);
        $this->assertInstanceOf('A', $result['obj_array']['A']);
    }
    
    /** \brief Tests to make sure users can set their own key for
     *         an object.
     * 
     * Tests demapping capabilities when users specify their own key
     * for a particular object. Ensures the object is of the specified type
     * and that the object is keyed to the specified key.
     */
    public function testDemapWithUserDefKey() {
        $test = array('bnirf=A');
        
        $result = $this->object->demap($test);
        
        $this->assertArrayHasKey('bnirf', $result);
        $this->assertInstanceOf('A', $result['bnirf']);
    }

}

?>
