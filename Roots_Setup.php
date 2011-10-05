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

/** \class Roots_Setup
 * \brief Begins building of ShrubRoots system.
 *
 * The Roots_Setup class is used to actually build ShrubRoots itself.
 * At the conclusion of the buildContainer method, it returns whatever has
 * been specified in the config file as the 'result' object. By default,
 * this is the ShrubRoots Roots_Container object, which itself contains all
 * build information for your own project as well as all objects needed to
 * build your project.
 * 
 * Roots_Setup uses an object implementing Shrub_IConfig to retrieve config
 * information. This information should specify a seed file (by default,
 * self_build.yaml) as well as any objects needed to unpack this seed file.
 * Unless you are extending or modifying ShrubRoots, you most likely will
 * not need to change the seed file except to specify your dependency mapping
 * and/or classmap. */
class Roots_Setup
{
    /** Configuration array, derived from config object.*/
    protected $config;
    
    /** \brief Roots_Setup constructor.
     *
     * @param Shrub_IConfig $config Configuration object.
     * 
     * Sets config object and config path, and turns the internal
     * autoload method on. */
    public function __construct(Shrub_IConfig $config) {
        $this->config = $config->retrieveConfigArray();
        $this->config['path'] = $config->retrieveConfigPath();
        spl_autoload_register(array('Roots_Setup', 'rootsAutoload'));
    }
    
    /** \brief Builds whatever object is specified as the "result" in the
     *         seed file.
     * 
     * The method builds a parser, unpacks the seedfile, then builds an
     * object builder and whatever demappers are needed. Using these objects,
     * whatever object was specified as the result in the config file is built
     * and returned. */
    public function buildContainer() {
        $this->checkConfig();
        $this->file_ext = $this->getConfigItem('autoload_file_extension');
        
        $parser = $this->makeObject('parser');
        $seedfile = $this->buildSeed($parser);
        
        $builder = $this->makeObject('object_builder');
        $obj_demap = $this->makeObject('object_demapper');
        $result = $this->getConfigItem('result');

        $demappers = $this->buildDemappers($builder, $obj_demap, $seedfile);
        
        $objects = $builder->buildObjects($seedfile, $demappers);
        
        unset($builder);
        unset($parser);
        unset($demappers);
        
        return $this->retrieveResultObject($result, $objects);
    }
    
    /** \brief Builds an array of demapper objects.
     *
     * @param Roots_IBuilding $obj_build An object builder.
     * @param Roots_IDemappable $obj_demap An object demapper.
     * @param array $seed_arr The array generated from the seedfile.
     * 
     * Builds whatever demappers are specified in the self_build seed file.
     * Works by obtaining the name of the sequence used to build the
     * demappers and the name of the mapping that should be linked to the
     * object demapper. */    
    protected function buildDemappers(Roots_IBuilding $obj_build,
                                      Roots_IDemappable $obj_demap,
                                      array $seed_arr) {
        $prebuild_seq = $this->getConfigItem('prebuild_name');
        $demap_link = $this->getConfigItem('initial_obj_demap_link');
        $demap_arr = array($demap_link => $obj_demap);
        $demappers = $obj_build->buildObjects($seed_arr, 
                                              $demap_arr, 
                                              $prebuild_seq);
        return $demappers;
    }
        
    /** \brief Checks the config array for necessary values.
     * 
     * Checks the config array for array keys that refer to items Roots_Setup
     * needs for proper operation. */
    protected function checkConfig() {
        $needed = array('autoload_file_extension', 'parser', 'seedfile',
                        'object_builder', 'object_demapper', 'result',
                        'prebuild_name', 'initial_obj_demap_link');
        foreach ($needed as $needed_item) {
            if (!array_key_exists($needed_item, $this->config)) {
                throw new Exception("Error in your config file: ".
                                    "Item $needed_item is missing. This ".
                                    "really shouldn't happen unless you ".
                                    "changed the config file somehow. ".
                                    "Please obtain a valid config file and ".
                                    "try again.");
            }
        }
    }
    
    /** \brief Retrieves an item from the config array.
     *
     * @param $key Name of item to retrieve.
     * 
     * Provides a shortcut for accessing the config array. */
    protected function getConfigItem($key) {
        return $this->config[$key];
    }

    /** \brief Retrieves the seed file and parses it into an array.
     *
     * @param Roots_IExtract $parser Parser used to deserialize seed file.
     * 
     * Obtains the path and name of the seed file, retrieves it, and parses
     * it into an array usable by ShrubRoots. */
    protected function buildSeed(Roots_IExtract $parser) {
        $filename = $this->getConfigItem('seedfile');
        $path = $this->getConfigItem('path');
        $file = $path.$filename;
        if (is_readable($path) && file_exists($file)) {
            $mltext = file_get_contents($file);
        
            $seed = $parser->extract($mltext);
            return array('seedfile' => $seed);
        }
        else {
            throw new Exception("Could not find seedfile at $file!");
        }
        
    }
    
    /** \brief Builds object of type specified by key, without parameters.
     *
     * @param $key Key in config array from which to retrieve object type.
     * 
     * Returns a new instance of whatever concrete object type is referenced
     * in the config array via key $key. Object type must not con */
    protected function makeObject($key) {
        $item = $this->getConfigItem($key);
        $ref_item = new ReflectionClass($item);
        $ctor = $ref_item->getConstructor();
        if (stristr($ctor, '<required>')) {
            throw new Exception("You are specifying initial objects in your ".
                                "config file that require constructors. ".
                                "Use the object builder to build such ".
                                "objects.");
        }
        return new $item;
    }

    protected function retrieveResultObject($result, array &$objects) {
        return $objects[$result];
    }
    
    /** \brief The actual autoload function for ShrubRoots internals.
     * @param $classname The name of the class to load.
     *
     * The file extension used in this method is obtained from the
     * configuration file. */
    public function rootsAutoload($classname) {
        $classphp = strtolower($classname).$this->file_ext;
        
        $filename = (__DIR__.DIRECTORY_SEPARATOR.$classphp);
        if (is_readable($filename)) {
            include_once($filename);
        }
        
    }    

    
}

?>
