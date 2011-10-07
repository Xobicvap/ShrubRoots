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

class Roots_YAMLSpycExtractorTest extends PHPUnit_Framework_TestCase
{
    protected $object;
    
    protected function setUp() {
        spl_autoload_register('autoloadForTesting');
        $this->object = new Roots_YAMLSpycExtractor;
    }
    
    public function testExtractBadText() {
        $badtext = 'zxcbn';
        $message = "ShrubRoots uses a version key to determine ".
                                "if its parsers are opening ShrubRoots ".
                                "mapping files. The file you have used does ".
                                "not include version information as its ".
                                "key => value pair. Please correct this or ".
                                "use another file. ";
        $this->setExpectedException('Exception', $message);
        
        $result = $this->object->extract($badtext);

    }
    
    public function testExtract() {
        $text = 'version: 0.995';
        $result = $this->object->extract($text);
        var_dump($result);
    }
    
}    

?>
