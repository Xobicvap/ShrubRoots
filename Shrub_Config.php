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

/** \class ShrubConfig
 * \brief Configuration file parser
 * 
 * ShrubConfig is a simple configuration file (.ini) file parser that also 
 * allows a (very basic) config path to be set.
 *
 * As of this version (0.99), you must place your config.ini file (and it must
 * be named 'config.ini' in a /config directory one level up from the document
 * root. You can change this behavior in the constructor method. */
class Shrub_Config implements Shrub_IConfig
{    
    
    /** The path to the config.ini file used for configuration. */
    protected $ini_file;
    
    /** The array produced from the PHP native function parse_ini_file */
    protected $config_array;
    
    /** The full path to the /config directory. Useful if you put other
     * configuration files into /config files. */
    protected $config_path;
    
    /** \brief ShrubConfig constructor method.
     * \param $config_folder Folder (underneath ShrubRoots base directory)
     *                       containing config files. Default is 'config'.
     * \param $inifile Name of config file. Default is 'config.ini'.
     *  
     * Collects the current working directory, obtains the config path
     * (as said earlier, can be useful later), uses this to get the
     * path for the 'config.ini' file, then parses the ini file into
     * an array.
     *
     * 2nd param to parse_ini_file indicates that sections 
     * are maintained in resultant array. */     
    public function __construct($config_folder = 'config', 
                                $inifile = 'config.ini',
                                $basepath = __DIR__) {
        
        /* obtain current working directory and add the $config_folder
         * address to it, then get the ini file location */
        $this->config_path = $basepath.DIRECTORY_SEPARATOR.
                             $config_folder;
        $this->ini_file = $this->config_path.DIRECTORY_SEPARATOR.$inifile;
        
        // fail if file does not exist or is not readable
        if (!is_readable($this->ini_file)) {
            throw new Exception("Could not find or read configuration file.");
        }
        
        // parse ini file for values
        $this->config_array = parse_ini_file($this->ini_file, true);       
    }
    
    /** \brief Getter method for retrieving the configuration array.
     *
     * Use this in any objects utilizing ShrubConfig to obtain
     * the configuration set given by 'config.ini'. */    
    public function retrieveConfigArray() {
        return $this->config_array;
    }
    
    /** \brief Getter method for config directory path.
     *
     * Returns the full path to the config directory. */    
    public function retrieveConfigPath() {
        return $this->config_path;
    }

}

?>
