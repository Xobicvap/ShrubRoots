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

/** \class Roots_ObjectBuilder
 * \brief Directs building of objects via demappers.
 *
 * The Roots_ObjectBuilder class is used to direct and supervise
 * the building of the objects stored in the Container. 
 * 
 * The buildObjects interface method is used by outside objects to perform
 * the actual building of objects. At the close of this method, if an object has
 * been set in the dependency mapping as a container reference, the
 * ObjectBuilder will inject this reference post-build into all objects that
 * require it. */
class Roots_ObjectBuilder implements Roots_IBuilding, 
        Roots_IContainerInjecting
{
    /** Array of map arrays parsed in from files. */
    protected $maps;
    
    /** Array of demapper objects. */
    protected $demappers;
    
    /** File extension to load, used by autoloader. */
    protected $file_ext;
    
    /** \brief Roots_ObjectBuilder constructor.
     *
     * @param array &$maps Array of map arrays.
     * @param array &$demappers Array of demapper objects.
     * 
     * Sets (optional) maps and demappers. */
    public function __construct(array &$maps = null,
                                array &$demappers = null) {
        $this->maps = $maps;
        $this->demappers = $demappers;
        
    }
    
    /** \brief Injects a container reference into an object.
     *
     * @param Roots_IObtaining $repo Container to inject.
     * @param Roots_IContainerAccepting $dest Object in which to do injection.
     * 
     * This is an interface method that allows an object to be given a
     * reference to a container object that implements the Roots_IObtaining
     * interface method getBuiltItems(). */
    public function injectContainerRef(Roots_IObtaining $repo,
                                       Roots_IContainerAccepting $dest) {
        $dest->acceptContainerRef($repo);
    }
    
    /** \brief Does the actual work of injecting container references.
     *
     * @param array &$reflist Container reference list.
     * @param array &$objs Collection of objects
     * 
     * The reflist parameter passed into this function is obtained from the
     * dependency mapping; it represents which objects should be injected
     * with which container reference. */
    protected function setContainerRef(array &$reflist, array &$objs) {
        $cont = key($reflist);
        $injectinto = current($reflist);
        
        $cont_obj = $objs[$cont];
        if (is_array($injectinto)) {
            foreach($injectinto as $obj) {
                $this->injectContainerRef($cont_obj, $objs[$obj]);
            }
        }
        else {
            $this->injectContainerRef($cont_obj, $injectinto);
        }

    }
    
    /** \brief Determines whether or not to set container references.
     *
     * @param array $depmap
     * @param array $objects 
     * 
     * Obtains the container reference list from the dependency mapping using
     * getDepMapArray() and checks to make sure that 1) said list actually
     * exists and 2) is an array. If both criteria are met, the 
     * setContainerRef method is called. */
    protected function decideContainerRef(array &$depmap, array &$objects) {
        $cont_ref = $this->getDepMapArray($depmap, 'container_ref', true);
        if ($cont_ref !== false && is_array($cont_ref)) {
            $this->setContainerRef($depmap, $objects);
        }
    }
    
    /** \brief Builds all objects according to dependency mapping.
     *
     * @param array $maps
     * @param array $demappers
     * 
     * This method is basically the workhorse of the entire ShrubRoots
     * container system. The odd syntax in the first foreach loop is there
     * to give the method a fallback if there were no parameters passed in.
     * 
     * Once the initial variables are decided upon, the method builds the
     * objects described in the dependency mapping in the order given by
     * said mapping. If a container reference list exists in the 
     * dependency mapping, the method calls the appropriate methods to ensure
     * that container references are set where necessary. */
    public function buildObjects(array &$maps = null,
                                 array &$demappers = null,
                                 $seq_name = null) {
        $check_vars = array('maps', 'demappers');

        /* if no necessary variables were passed into method, see
         * if they had been set via constructor; if so, use those,
         * if not, throw an exception */
        foreach ($check_vars as $var) {
            $vartype = &${$var};
            if ($vartype === null && $this->$var !== null) {
                $vartype = &$this->$var;
            }
            if ($vartype === null && $this->$var === null) {
                throw new Exception("ObjectBuilder has received no $var ".
                                    "to use in method buildObjects.");
            }
        }
        
        $dep_map = $this->retrieveDepMap($maps);

        $demap_sequence = $this->getDemapSequence($dep_map, $seq_name);
        
        $objects = array();
        
        foreach ($demap_sequence as $demap_type) {
            $type_check = 
                $this->checkDemapType($demap_type, $demappers, $dep_map);
            if ($type_check !== false) {
                $objs = $demappers[$demap_type]->demap($dep_map[$demap_type]);
            
                if (array_key_exists('factories', $objs)) {
                    $built_objs = $this->buildCompileTimeObjs($objs);
                    $objs = array_merge($objs, $built_objs);
                }
                $objects = array_merge($objects, $objs);
            }
        }
        
        $this->decideContainerRef($dep_map, $objects);
        
        return $objects;
    }
    
    protected function checkDemapType($demap_type, 
                                      array &$demappers,
                                      array &$depmap) {
        if (!array_key_exists($demap_type, $demappers)) {
            throw new Exception("Mapping '$demap_type' has not been ". 
                                "keyed to a demapper! Please correct ".
                                "this in your dependency mapping file!");
        }
        if (!array_key_exists($demap_type, $depmap)) {
            return false;
        }
    }
    
    /** \brief Retrieves the dependency mapping from the maps array.
     *
     * @param array $maps The maps array.
     * 
     * The Roots_Setup object uses the parsed contents of the self_build.yaml
     * file, keyed to "seedfile", to build the rest of ShrubRoots. If this
     * key is present in the maps array, the ObjectBuilder assumes it is
     * to build ShrubRoots, and returns the corresponding map.
     * 
     * Otherwise, the ObjectBuilder looks for a dep_map_name key in the
     * maps array. This is set by default in the self_build.yaml file and
     * should not be removed. The dep_map_name key references the name of
     * your dependency map. Using this key, your dependency map is returned.
     * 
     * If the dep_map_name key is not set, the default name for your
     * dependency map is "dep_map". If neither the dep_map_name key nor the
     * dep_map key are set, an exception is thrown. */
    protected function retrieveDepMap(array &$maps) {
        if (array_key_exists('seedfile', $maps)) {
            return $maps['seedfile'];
        }
        $dm_name_exists = array_key_exists('dep_map_name', $maps);
        $dm_name = ($dm_name_exists === true) ?
            $this->getDepMapArray($maps, 'dep_map_name') :
            'dep_map';
        $depmap = $this->getDepMapArray($maps, $dm_name);
        if ($depmap === false) {
            throw new Exception("Could not find your dependency mapping!".
                                "Please check the filename of the ".
                                "dependency map in the self_build.yaml" .
                                "file, and make sure that the dep_map_name ".
                                "field in that file is set to whatever you ".
                                "are calling your dependency map.");
        }
        return $depmap;
    }
    
    /** \brief Obtains the sequence in which to use the demapper objects.
     *
     * @param array &$depmap Dependency mapping array.
     * 
     * Gets the demapping sequence from the dependency mapping. A default
     * sequence is set via this method if the mapping file lacks a demapping
     * sequence. */
    protected function getDemapSequence(array &$depmap, $seq_name = null) {
        if ($seq_name === null) {
            $seq_name = 'sequence';
        }
        $seq = $this->getDepMapArray($depmap, $seq_name, true);
        if ($seq === false || !$seq || !is_array($seq)) {
            $seq = array('variables', 'objects');
        }
        return $seq;
    }    
    
    /** \brief Obtains a list from the dependency mapping array.
     *
     * @param array &$depmap Dependency mapping array.
     * @param $name Name of list to obtain.
     * @param bool $unset Describes whether or not to delete the list from
     *                    the mapping.
     * If the dependency mapping array contains the desired list, it is 
     * returned, otherwise returns false. If the unset flag is set to true
     * (optional) the corresponding list is unset from the dependency mapping.
     */
    protected function getDepMapArray(array &$depmap, $name, $unset = false) {
        $array = (array_key_exists($name, $depmap)) ?
            $depmap[$name] :
            false;

        if ($unset === true && $array !== false) {
            unset($depmap[$name]);
        }
        return $array;
    }
    
    /** \brief Gets the factory sequence from the factories list.
     *
     * @param array &$objs A list from which to get the sequence.
     * 
     * If the 'sequence' key exists in the $objs list, returns the
     * array corresponding to the 'sequence' key, otherwise returns null.
     * If extending ShrubRoots, be careful not to use this method on the
     * actual dependency mapping list, because it will get you the 
     * demapper sequence instead. */
    protected function getFactorySequence(array &$objs) {
        $factory_sequence = (array_key_exists('sequence', $objs)) ?
            $objs['sequence'] :
            null;
        return $factory_sequence;
    }
    
    /** \brief Uses factories to build complex objects at compiletime.
     *
     * @param array $fact_list List of factory information.
     * 
     * Using the sequence obtained via getFactorySequence, builds complex
     * objects using factories that implement the Roots_ICompileObjs 
     * interface's public buildPremapped method. */
    protected function buildCompileTimeObjs(array &$fact_list) {
        $seq = $this->getFactorySequence($fact_list);
        $fact_objs = $fact_list['factories'];
        $fact_built = array();
        
        $premaps = $this->filterFactoryObjs($fact_objs);
        
        if ($seq !== null || !empty($seq)) {
            // make sure all objects in sequence implement buildPremapped()
            $this->testSequence($seq, $premaps);
            
            foreach ($seq as $obj_name) {
                $fact_built = 
                        array_merge($fact_built, 
                        $fact_objs[$obj_name]->buildPremapped($fact_built));
            }
        }
        return $fact_built;
    }
    
    /** \brief Public interface method for using factories.
     *
     * @param array &$fact_list List of factory objects.
     * @param $obj_name Name of object type to build. Keyed to
     *                       corresponding factory object.
     * @param array &$params Parameters for factory object's build method.
     * 
     * Returns an object of type $obj_name built using the corresponding
     * factory object. This method is meant to be called from the container
     * by any objects that have a container reference. Said objects
     * have absolutely no direct connection with this method. */
    public function buildFromFactory(array &$fact_list, 
                                     $obj_name,
                                     array &$params = null) {
        
        $factory = $fact_list[$obj_name];
        return $factory->build($params);
    }
    

    /** \brief  Returns a list of only those factories which implement
     *         buildPremapped().
     * @param array &$fact_list Factory list to check.
     * 
     * Keeps (and returns the list of) only those factory objects
     * that implement buildPremapped().*/
    protected function filterFactoryObjs(array &$fact_list) {
    	foreach ($fact_list as $fact_name => $fact_obj) {
    		if ($fact_obj instanceof Roots_ICompileObjs) {
    			$premap_fact[$fact_name] = $fact_obj;
    		}
    	}
    	return $premap_fact;
    }    
    
    /** \brief  Ensures all objects in a sequence implement build_premapped().
     * @param array &$seq The sequence to check
     * @param array &$premaps The list of validated factory objects.
     * 
     * Throws exception if any factory in a sequence does not implement 
     * buildPremapped().*/
    protected function testSequence(array &$seq, array &$premaps) {
    	foreach ($seq as $fact_name) {
    		if (!array_key_exists($fact_name, $premaps)) {
    			throw new Exception("Factory $fact_name in sequence ".
                                "does not implement buildPremapped and thus ".
                                "cannot be used to build objects at ".
                                "compiletime. Have this factory implement ".
                                "Roots_ICompileObjs.");
    		}
    	}
    }    
    
    /** \brief Gets a file extension for use by the autoload method.
     *
     * Attempts to load the file extension listed in the classmap array. 
     * Classmaps can either be legitimate arrays built using
     * the ClassDumper object, or can simply be a string containing the
     * desired file extension. If no classmap is found at all, or it
     * does not contain a file extension (must contain a period to be
     * read as such), '.php' is used by default. */
    protected function getFileExt() {
        if (array_key_exists('classmap', $this->maps)) {
            $classmap = $this->maps['classmap'];
            
            if (is_array($classmap)) {
                if (array_key_exists('file_ext', $classmap)) {
                    $file_ext = $classmap['file_ext'];
                    unset($classmap['file_ext']);
                    return $file_ext;
                }
            }    
            else if (!is_array($classmap) && strpos($classmap, '.')) {
                return $classmap;
            }
        }
        
        // no conditions above have been met
        return '.php';
        
    }

    /** \brief Turns autoload on or off. 
     * @param $toggle String value that should be either 'on' or 'off'.
     * 
     * Checks the toggle string to make sure it's either on or off, then
     * calls the autoload_switch method. The latter method is private
     * and internal to this class. This method is meant as a public
     * interface to the autoload_switch method. */
    public function toggleAutoload($toggle) {
        $on_off = array('on', 'off');
        if (is_string($toggle) && in_array($toggle, $on_off)) {
            $autoload_func = array('Roots_ObjectBuilder', 'buildAutoload');
            $this->autoloadSwitch($autoload_func, $toggle);
        }
    }
    
    /** \brief Method that actually turns the autoload function on or off.
     * @param array &$load_func Contains the class and autoload function name.
     * @param $testcond A string, set to either 'on' or 'off'.
     *
     * Tests whether or not the autoload function is already loaded first;
     * if it is loaded and we're trying to register the autoload function,
     * then do not. If it is not loaded and we're trying to unregister it
     * (i.e. turn it off), then do not. Kind of complicated logic but not
     * doing it in the manner below leads to a lot of nested if statements
     * and/or repeated code. */
    protected function autoloadSwitch(array &$load_func, $testcond) {
        $switch = array('on' => false, 'off' => true);
        $test = $this->testAutoloadFuncExists($load_func);
        
        if ($test === $switch[$testcond]) {
            $testcond === 'on' ?
                spl_autoload_register($load_func, false, true) :
                spl_autoload_unregister($load_func);
        }
    }

    /** \brief Checks if the autoload function has already been set.
     * @param array &$load_func Contains the class and autoload function name.
     *
     * Tests if the autoload function exists in the spl_autoload_functions
     * array. There's an inversion in the final return value because
     * otherwise we get weird logic like true = false. ^_^ */
    protected function testAutoloadFuncExists(array &$load_func) {
        $autoload_funcs = spl_autoload_functions();
        if ($autoload_funcs === null || $autoload_funcs === false) {
            return false;
        }
        
        $arrtest = array_intersect_assoc($autoload_funcs, $load_func);
        $result = empty($arrtest);
        return !$result;
    }    
    
    /** 
     * \brief  The autoload function used by ShrubRoots to build objects.
     * @param $classname The name of the class to load.
     *
     * Loads files needed for instantiated objects according to the
     * filename convention: 
     * projectname_classname.file_extension */
    public function buildAutoload($classname) {
        $classname = strtolower($classname);
        
        if (!isset($this->file_ext)) {
            $this->file_ext= $this->getFileExt();
        }
            
        $classname = $classname.$this->file_ext;
        if (!array_key_exists('classmap', $this->maps)) {
            $path = __DIR__.DIRECTORY_SEPARATOR;
        }
        else {
            $path = array_search($classname, $this->maps['classmap']);
        }

        if ($path !== false && is_readable($path)) {
            $includefile = $path.$classname.$this->file_ext;
            include_once($includefile);
        }
    }

}

?>
