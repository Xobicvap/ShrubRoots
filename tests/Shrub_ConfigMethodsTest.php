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

class Shrub_ConfigMethodsTest extends PHPUnit_Framework_TestCase 
{

    private $base;
    private $path;
    private $object;
    
    protected function setUp() {
        chdir('c:\php\ShrubRoots');
        $this->base = getcwd();
        $this->path = 'config';

        spl_autoload_register('autoloadForTesting');        
        
        $this->object = new Shrub_Config($this->path, 'test.ini', $this->base);
        $this->conf = $this->object->retrieveConfigArray();
    }
    
    protected function tearDown() {
        unset($this->base);
        unset($this->path);
        unset($this->object);
        unset($this->conf);
        spl_autoload_unregister('autoloadForTesting');        
    }
    
    public function testRetrieveReturnsArray() {      
        $conf_isarr = is_array($this->conf);
        
        $this->assertTrue($conf_isarr);
    }
    
    public function testArrayStructure() {
        $heading = 'section';
        $firstkey = 'name';
        $firstvalue = 'value';
        
        $this->assertArrayHasKey($heading, $this->conf);
        $this->assertArrayHasKey($firstkey, $this->conf[$heading]);
        $this->assertSame($firstvalue, $this->conf[$heading][$firstkey]);
    }
    
    public function testRetrieveConfigPath() {
        $obtainedpath = $this->object->retrieveConfigPath();
        $expectedpath = $this->base.DIRECTORY_SEPARATOR.$this->path;
        $this->assertSame($obtainedpath, $expectedpath);
    }
}

    
?>
