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

class Shrub_ConfigTest extends PHPUnit_Framework_TestCase {
    
    protected function setUp() {
        //chdir('c:\php\www\srtest');
        spl_autoload_register('autoloadForTesting');        
        
        $this->path = getcwd();
        $this->message = "Could not find or read configuration file.";
        $this->setExpectedException('Exception', $this->message);
    }

    protected function tearDown() {
        spl_autoload_unregister('autoloadForTesting');
        unset($this->object);              
    }    
    
    public function testConstructorWithGibberishPath() {
        $gibberish = 'coaibgbgsiobg';
        $this->object = new Shrub_Config('config', 'config.ini', $gibberish);
    }
        
    public function testConstructorWithNonexistentPath() {
        $nonexistent = getcwd() . 'kittens';
        $this->object = new Shrub_Config('config', 'config.ini', $nonexistent);
    }

    public function testConstructorWithBadConfigFile() {
        $badtest = 'ttessst.ini';
        $this->object = new Shrub_Config('config', $badtest, $this->path);
    }
    
}



?>
