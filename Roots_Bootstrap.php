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

class Roots_Bootstrap
{

    protected $apckey;
    
    public function __construct($apcfolder) {
        $this->apckey = 'ShrubRoots';
        define('ROOTS_VERSION', '0.99');
        define('ROOTS_NAME', $this->apckey);
    }
    
    /** ShrubRoots bootstrap file. */
    public function cacheCheck() {
        $container = apc_fetch($this->apckey);
        if ($container === false) {
            $container = $this->bootstrapLoad();
        }
        
        return $container;
    }
    
    public function bootstrapLoad() {
        require 'Roots_ExceptionHandler.php';
        require 'Roots_Setup.php';
        require 'Shrub_IConfig.php';
        require 'Shrub_Config.php';
        
        $config = new Shrub_Config;
        $exc_handler = new Roots_ExceptionHandler($config, 
                                                  ROOTS_NAME, ROOTS_VERSION);        
        $exc_handler->registerHandlers();
        
        $setup = new Roots_Setup($config);
 
        $container = $setup->buildContainer();

        $container->loadObjects();
        
        apc_store($this->apckey, $container);
        
        return $container;
    }
}

?>
