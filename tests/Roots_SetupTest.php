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

/** \class Roots_SetupTest
 * \brief Tests behavior of the Roots_Setup class.
 * 
 * Ensures that setup logic for ShrubRoots performs as expected.
 * Much less rigorous than other tests because it mainly uses other objects;
 * this object itself should not care what these objects return. */
class Roots_SetupTest extends PHPUnit_Framework_TestCase {
    
    /** Roots_Setup object instance.*/
    protected $object;
    
    /** \brief Test fixture.
     * 
     * Fixture for testing; registers autoload and creates an instance
     * of Roots_Setup. */
    protected function setUp() {
        spl_autoload_register('autoloadForTesting');
        $config = new Mock_Config;
        $this->object = new Roots_Setup($config);
    }
    
    /** \brief Test fixture destructor.
     * 
     * Unregisters autoload and unsets Roots_Setup instance. */
    protected function tearDown() {
        spl_autoload_unregister('autoloadForTesting');
        unset($this->object);
    }
    
    /** \brief Tests normal operation of the buildContainer method.
     * 
     * Tests to ensure that the buildContainer method operates properly.
     * 
     * This test method uses several mock objects in order to allow
     * the buildContainer method to operate successfully. The two most
     * important parts of the method involve calls to the protected methods
     * buildDemappers and the object builder method buildObjects. The 
     * buildDemappers method just mentioned involves a call to the
     * buildObjects method as well. The buildDemappers method is directed
     * by the mock config object to build a mock demapper object; this
     * object is passed into the final buildObjects method.
     * 
     * Ordinarily, the buildObjects method uses whatever demappers are
     * passed to it to construct the objects specified in the dependency
     * mappings. However, in this case, the mock builder's buildObjects
     * method simply returns the demapper passed to it. */
    public function testBuildContainer() {
        $result = $this->object->buildContainer();
        $this->assertInstanceOf('Mock_Demapper', $result);
    }
    
    /** \brief Tests to ensure a config file without necessary
     *         parameters throws an exception properly.
     * 
     * ShrubRoots depends on a number of items in the config array
     * to get itself started. This method tests to make sure Roots_Setup
     * throws an exception if one of these items is not present. */
    public function testBadConfig() {
        unset($this->object);
        $config = new Mock_Config;
        $new_config = array('seedfile' => 'a.php',
                            'parser' => 'Roots_YAMLSpycExtractor',
                            'object_builder' => 'Mock_Builder',
                            'object_demapper' => 'Mock_Demapper',
                            'result' => 'fabzx',
                            'prebuild_name' => 'prebuild',
                            'initial_obj_demap_link' => 'fabzx');   
        
        $config->changeConfigArray($new_config);
        $this->object = new Roots_Setup($config);
        
        $message = "Error in your config file: ".
                   "Item autoload_file_extension is missing. This ".
                   "really shouldn't happen unless you ".
                   "changed the config file somehow. ".
                   "Please obtain a valid config file and ".
                   "try again.";
        
        $this->setExpectedException('Exception', $message);
        
        $result = $this->object->buildContainer();
    }
    
}

?>
