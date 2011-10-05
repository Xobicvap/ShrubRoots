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

class Roots_YAMLSpycExtractor implements Roots_IExtract
{

    private $spyc_obj;

    public function __construct() {
        if (spl_autoload_functions() === FALSE) {
            // if there are no autoload functions defined,
            // the spyc.php implementation file MUST be in same
            // directory as this file
            include_once('spyc.php');
        }
        $this->spyc_obj = new Spyc;
    }
    
    public function extract($text) {
        $yaml_array = $this->spyc_obj->load($text);
        /* Spyc returns an array from ANY string given to it, so
         * a check for whether or not $yaml_array is an array is meaningless;
         * the only thing Spyc balks at are resources and these 
         * trigger an explode() error */
        
        reset($yaml_array);
        $version = key($yaml_array);
        
        if ($version !== 'version') {
            throw new Exception("ShrubRoots uses a version key to determine ".
                                "if its parsers are opening ShrubRoots ".
                                "mapping files. The file you have used does ".
                                "not include version information as its ".
                                "key => value pair. Please correct this or ".
                                "use another file. ");
        }
        return $yaml_array;
    }
         
}

?>
