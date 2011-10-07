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

/** \class Roots_VariableDemapperTest
 * \brief Unit test for Roots_VariableDemapper.
 * 
 * Tests all existing variable mapping types. If any are added,
 * be sure to test them before deploying your fork/modification. */
class Roots_VariableDemapperTest extends PHPUnit_Framework_TestCase
{
    
    /** Roots_VariableDemapper instance used for testing. */
    private $object;
    
    /** \brief Test fixture.
     * 
     * Fixture for testing; registers autoload, loads a parser
     * and variable demapper object. */
    protected function setUp() {
        spl_autoload_register('autoloadForTesting');
        
        $parser = new Roots_YAMLSpycExtractor();

        $this->object = new Roots_VariableDemapper($parser);
    }
    
    /** \brief Test fixture destructor.
     * 
     * Unsets test fixture objects and unregisters testing autoload. */
    protected function tearDown() {
        spl_autoload_unregister('autoloadForTesting');
        unset($this->object);
    }
    
    /** \brief Tests correct behavior if a variable is not named.
     * 
     * Should throw an exception if a variable is encountered without
     * a name. */
    public function testDemapNoName() {
        $test = array(array('type' => 'int', 'value' => 5));
        $this->setExpectedException('Exception',
                "Each variable mapping must have a name.");
        $this->object->demap($test);
    }
    
    /** \brief Tests correct behavior if a variable's type or value are absent.
     * 
     * VariableDemapper should throw an exception if a variable is
     * found missing a type or value. */
    public function testDemapNoTypeorValue() {
        $test = array('variable' => array('value' => 5));
        $this->setExpectedException('Exception',
                "Variable mappings must have a type and value.");
        $this->object->demap($test);
        
        $test = array('variable' => array('type' => 'int'));
        $this->object->demap($test);
    }
    
    /** \brief Tests behavior of int, float, and string variable demapping.
     * 
     * Tests to ensure correct values are retrieved for sample int,
     * float, and string variables. */
    public function testDemapIntFloatString() {
        $inttest = array('inttest' => 
            array('type' => 'int', 'value' => 5));
        $floattest = array('floattest' =>
            array('type' => 'float', 'value' => 3.147));
        $stringtest = array('stringtest' =>
            array('type' => 'string', 'value' => 'borf'));
        
        $intresult = $this->object->demap($inttest);
        $floatresult = $this->object->demap($floattest);
        $stringresult = $this->object->demap($stringtest);
        
        $this->assertSame(5, $intresult['inttest']);
        $this->assertSame(3.147, $floatresult['floattest']);
        $this->assertSame('borf', $stringresult['stringtest']);
        
    }
    
    /** \brief Tests if array demapping behavior behaves properly.
     * 
     * Tests array variable demapping. First test array demonstrates
     * simple array demapping. Second test array references first array
     * and contains two subarrays. */
    public function testDemapArray() {
        $baf = array('baf' => array(
               'type' => 'int',
               'value' => '6'
               ));
        
        $useless = $this->object->demap($baf);
        $arrtest = array('arrtest' => array(
                  'type' => 'array',
                  'value' => array(
                      'zubaf' => 'baf',
                      'borf' => array(
                          'type' => 'int',
                          'value' => '4'),
                      'frorf' => array(
                          'type' => 'string',
                          'value' => 'vof'
                          )
                      )
                  ));

        $result = $this->object->demap($arrtest);        
        $isarr = is_array($result);
        $hassubarr = is_array($result['arrtest']);
        $this->assertTrue($isarr);
        $this->assertTrue($hassubarr);
        $this->assertSame(6, $result['arrtest']['zubaf']);
        $this->assertSame(4, $result['arrtest']['borf']);
    }
    
    /** \brief Tests to ensure text files can be loaded properly.
     * 
     * Loads a PHP file as text, and ensures the standard <?php header
     * is present.
     * 
     * The location of the text file to load can be changed, but
     * it should be a PHP file in order to agree with the test. */
    public function testTextFile() {
        $texttest = array('text' => array(
            'type' => 'text_file',
            'value' => 
                __FILE__));
        $result = $this->object->demap($texttest);
        $header = '<?php';
        $res_hdr = substr($result['text'], 0, 5);
        $this->assertSame($header, $res_hdr);

    }

    /** \brief Tests to ensure include files can be loaded properly.
     * 
     * Loads an include file as a variable mapping, with the autoload
     * method turned off beforehand, then tries to instantiate an object
     * specified in said include file. If the include had failed, 
     * it will be impossible to instantiate the object.
     * 
     * The location of the include file to load can be changed. */
    public function testIncludeFile() {
        $test = array('incfile' => array(
            'type' => 'include_file',
            'value' =>
                'c:\\php\\ShrubRoots\\tests\\a.php'));
        spl_autoload_unregister('autoloadForTesting');
        /*require 'c:\\php\\ShrubRoots\\tests\\a.php';
        $jerome = get_included_files();
        if (in_array('c:\\php\\ShrubRoots\\tests\\a.php', $jerome)) {
            echo "POOOOOOP!";
        }*/
        $result = $this->object->demap($test);
        
        // if including the file does not work, neither will this;
        // autoload is turned off temporarily
        $a_obj = new A();
        
        $this->assertSame('included', $result['incfile']);
        $this->assertInstanceOf('A', $a_obj);
        
    }
    
    /** \brief Tests to ensure file handle errors are handled properly.
     *
     * Loads a file handle for a nonexistent file. An exception
     * should be thrown when this happens. 
     * 
     * @expectedException PHPUnit_Framework_Error
     */    
    public function testNonexistentFileHandle() {
        $test = array('file_handle' => array(
            'type' => 'file_handle',
            'value' => 'c:\\php\\www\\srtest\\nonexistent.php'));
        $expected = 'Could not open file '.
                'c:\\php\\www\\srtest\\nonexistent.php';
        //$this->setExpectedException('Exception', $expected);
        $result = $this->object->demap($test);
        
    }
    
    /** \brief Tests to ensure file handles can be loaded properly.
     *
     * Loads a file handle for a PHP file, then checks the header
     * against the expected value (i.e. "<?php"). */        
    public function testFileHandle() {
        $test = array('file_h' => array(
            'type' => 'file_handle',
            'value' => 'c:\\php\\www\\srtest\\a.php'));
        $result = $this->object->demap($test);
        
        $fh = $result['file_h'];
        $res_hdr = fread($fh, 5);
        
        $header = '<?php';
        $this->assertSame($header, $res_hdr);        
        
    }
    
    /** \brief Tests to ensure parsable files can be loaded properly.
     * 
     * Loads a parsable file, in this case the self_build file because
     * this is unlikely to change much, and ensures that it is converted
     * into an array. The correctness of the array is not the responsibility
     * of the variable demapper, so this is unimportant, and in fact the
     * array key checking should probably be removed. */
    public function testParsableFile() {
        $test = array('parsable_file' => array(
            'type' => 'parsable_file',
            'value' => 'c:\\php\\www\\srtest\\self_build.yaml'));
        $result = $this->object->demap($test);
        
        $arrtest = is_array($result);

        $this->assertTrue($arrtest);
        $this->assertArrayHasKey('objects', $result['parsable_file']);
        
    }
    
}

?>
