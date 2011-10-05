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

class Mock_Container implements Roots_IContaining
{
    
    private $setup;
    private $ctype;
    private $objlist;
    
    public function __construct(array &$setup_arr = null , $ctype = null) {
        $this->setup = $setup_arr;
        $this->ctype = $ctype;
    }
    
    public function retrieveItem($obj_name) {
        return $this->objlist[$obj_name];
    }
    
    public function getBuiltItems() {
        return $this->objlist;
    }
    
    public function acceptObj($name) {
        $this->objlist[$name] = new $name;
    }
    
}

?>
