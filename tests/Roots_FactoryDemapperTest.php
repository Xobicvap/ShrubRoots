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
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'testingautoload.php';

class Roots_FactoryDemapperTest extends PHPUnit_Framework_TestCase
{
    private $obj_demap;
    private $object;
    
    protected function setUp() {
        spl_autoload_register('autoloadForTesting');
        
        $cont = new Mock_Container;
        $this->obj_demap = new Roots_ObjectDemapper($cont);
        $this->object = new Roots_FactoryDemapper($this->obj_demap);
    }
    
    public function testDemapInvalidMap() {
        $test = array('sequence' => array('A', 'B', 'C'));
        $this->setExpectedException('Exception', 
                "Factory maps require both assignment lists and build lists.");
        $result = $this->object->demap($test);
    }
    
    public function testDemap() {
        $test = array('assignments' => array('A' => 'Mock_Factory'),
            'builds' => array('Mock_Factory' => array('A')),
            'sequence' => array('A'));

        var_dump($test);
        print_r($test['assignments']);
        $result = $this->object->demap($test);
        var_dump($result);

    }
}

?>
