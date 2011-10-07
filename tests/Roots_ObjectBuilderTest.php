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

/** \class Roots_ObjectBuilderTest
 * \brief Tests operation of Roots_ObjectBuilder.
 * 
 * (what the object builder is for and how we're testing it)
 */
class Roots_ObjectBuilderTest extends PHPUnit_Framework_TestCase
{
    /** Roots_ObjectBuilder object instance. */
    protected $object;
    
    /** \brief Test fixture.
     * 
     * Registers testing autoload function and instantiates the
     * Roots_ObjectBuilder class (without constructor parameters)
     * for testing.
     */
    protected function setUp() {
       spl_autoload_register('autoloadForTesting');
       $this->object = new Roots_ObjectBuilder;
    }
    
    /** \brief Test fixture destructor.
     * 
     * Unregisters autoload method and unsets Roots_ObjectBuilder instance. */
    protected function tearDown() {
        spl_autoload_unregister('autoloadForTesting');
        unset($this->object);
    }
    
    /** \brief Tests to make sure buildObjects fails without
     *         necessary parameters.
     * 
     * The buildObjects method of the object builder requires there to
     * be an array of maps and an array of demappers at the very least.
     * Either or both of these arrays can be passed in via the constructor
     * or via the buildObjects method itself, but they must be present, else
     * an exception is thrown. This method ensures this exception does indeed
     * get thrown when NEITHER arrays are present. */
    public function testBuildObjectsWithoutInfo() {
        $message = "ObjectBuilder has received no maps to use in method ".
                   "buildObjects.";
        $this->setExpectedException('Exception', $message);
        $result = $this->object->buildObjects();
        
    }
    
    /** \brief Tests to make sure buildObjects method fails if
     *         missing ONE of the necessary parameters.
     * 
     * Similar to testBuildObjectsWithoutInfo, but in this case a mock
     * maps array is passed to the buildObjects method. The method should
     * detect that no demappers array is present and should throw an
     * exception.
     */
    public function testBuildObjectsOneParam() {
        $message = "ObjectBuilder has received no demappers to use ".
                   "in method buildObjects.";
        $maps = array('a');
        $this->setExpectedException('Exception', $message);
        $result = $this->object->buildObjects($maps);
    }
    
    /** \brief Tests to make sure the buildObjects method fails if
     *         it cannot find a suitable dependency mapping in the maps
     *         array.
     * 
     * The buildObjects method looks for a key entitled either 'seedfile'
     * (if the ObjectBuilder is building ShrubRoots) or 'dep_map_name'.
     * The latter key references another key that is linked to the user's
     * dependency mapping, and is present in the self_build.yaml file.
     * If neither of these keys is found, buildObjects should fail
     * with an exception. */
    public function testBuildObjectsNoDMName() {
        $message = "Could not find your dependency mapping!".
                   "Please check the filename of the ".
                   "dependency map in the self_build.yaml" .
                   "file, and make sure that the dep_map_name ".
                   "field in that file is set to whatever you ".
                   "are calling your dependency map.";
        $map = array('maf' => 'baf');
        
        $this->setExpectedException('Exception', $message);
        $result = $this->object->buildObjects($map, $map);
        
    }
    
    /** \brief Tests if buildObjects can function if not given a sequence.
     * 
     * The buildObjects method requires a sequence in which to use the
     * demappers to build objects. If no sequence name is specified,
     * this defaults to 'sequence'. If there is no 'sequence' array in
     * the dependency mapping, the sequence itself is set such that
     * two mappings, 'variables' and 'objects' are specified, in that order.
     * 
     * This method tests if this process works properly, allowing the
     * ObjectBuilder to build objects (provided the mappings involved are
     * the default 'variables' / 'objects' mappings) with no sequence name
     * or even sequence specified.
     */
    public function testBuildObjectsNoSequence() {
        $premap = array('variables' => array('A'), 'objects' => array('A2=A'));
        $map = array('dep_map' => $premap);
        $obj_demap = new Roots_ObjectDemapper;
        $demappers = array('variables' => $obj_demap, 'objects' => $obj_demap);
        $result = $this->object->buildObjects($map, $demappers);
        $a_from_a = $result['A']->getFoob();
        $a_from_a2 = $result['A2']->getFoob();
        $this->assertSame($a_from_a, $a_from_a2);
    }
    
    public function testBuildObjectsBadSequence() {
        $premap = array('variables' => array('A'), 'objects' => array('A2=A'));
        $map = array('dep_map' => $premap);
        $obj_demap = new Roots_ObjectDemapper;
        $demappers = array('variables' => $obj_demap, 
                           'bobjects' => $obj_demap);
        $message = "Mapping 'objects' has not been ". 
                   "keyed to a demapper! Please correct ".
                   "this in your dependency mapping file!";
        $this->setExpectedException('Exception', $message);
        $result = $this->object->buildObjects($map, $demappers);
    }
    
    public function testBuildCompileTimeObjs() {
        $fact_map = array('assignments' => 
                            array('D' => 'Mock_ComplexFactory'),
                          'builds' => 
                            array('Mock_ComplexFactory' =>
                                  array('Roots_ObjectDemapper')),
                          'sequence' => array('D'));
        $fmap = array('factory_build' => $fact_map,
                      'sequence' => array('factory_build'));
        $obj_demap = new Roots_ObjectDemapper;
        $fact_demap = new Roots_FactoryDemapper($obj_demap);
        $demappers = array('factory_build' => $fact_demap);
        $map = array('dep_map_name' => 'factbuild',
                     'factbuild' => $fmap);
        
        $objects = $this->object->buildObjects($map, $demappers);
        $this->assertArrayHasKey('C', $objects);
        
        $this->assertInstanceOf('C', $objects['C']);

    }
    
    public function testBuildFromFactory() {
        $obj_demap = new Roots_ObjectDemapper;
        $fact = new Mock_ComplexFactory($obj_demap);
        
        $fact_list = array('factory' => $fact);
        $build_times = array('build_times' => 5);
        $fact_objs = $this->object->buildFromFactory($fact_list, 'factory',
                                                     $build_times);
        var_dump($fact_objs);
    }
}
?>
