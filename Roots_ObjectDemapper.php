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

/** \class Roots_ObjectDemapper
 *  Given a list of dependencies, builds the objects contained therein.
 *
 * The Roots_ObjectDemapper object parses a dependencies list in order to
 * build objects. If an object is listed without any associations to it,
 * it is assumed the object requires no arguments to its constructor and
 * is simply instantiated. If an object has associations listed next to
 * or under it, it is assumed these associations are dependencies to be
 * injected via the constructor. 
 * 
 * The clone list passed in tells objects that a dependency should be
 * cloned rather than passed as a reference to an existing object. 
 * 
 * It should be noted that objects made using the ObjectDemapper do
 * require that some sort of autoload function has been loaded. Most
 * third-party libraries have their own autoload methods; these must
 * be loaded prior to using the ObjectDemapper via including them in
 * the constants section of the dependency mapping. */
class Roots_ObjectDemapper implements Roots_IDemappable,
        Roots_IContainerAccepting
{

    /** List of objects to clone (as opposed to passing refs to existing
        objects) */
    protected $clone_list = array();
    
    /** The pool of objects that have been built. */
    protected $source_pool = array();
    
    /** \brief Roots_ObjectDemapper constructor.
     * @param $repo A reference to the container object, which
                    can be copied into any objects that need it (i.e.
                    objects that need access to the container object's
                    factories or to the container's objects themselves).
     * @param array &$clone_list A list of objects to clone.
     * 
     * The Roots_ObjectDemapper constructor sets a reference to the
     * Roots_Container object, and sets an optional clone list passed in
     * as an argument.
     */
    public function __construct(Roots_IObtaining $repo = null, 
                                array &$clone_list = null) {
        $this->source_pool['Container'] = $repo;
        $this->source_pool['Object_Demapper'] = $this;
        if (isset($clone_list)) {
            $this->clone_list = $clone_list;
        }
    }
    
    /** \brief Sets a reference to a container from which to get objects.
     * 
     * @param Roots_IObtaining $repo Container reference.
     * 
     * Sets the 'Container' object in the source pool to the $repo reference.
     */
    public function acceptContainerRef(Roots_IObtaining $repo) {
        $this->source_pool['Container'] = $repo;
    }
    
    /** \brief Internal, recursive method for reading a dependency list.
     * @param array &$obj_map The dependency list to be read and demapped.
     * @param array &$parent_arr The parent array of a dependency list.
     *
     * The read_object() method parses a dependency list. A dependency list is
     * simply a mapping of objects to their dependencies (passed in as 
     * constructor arguments). Objects without dependencies are mapped to the
     * string 'none'. The names of these objects are passed to the
     * get_object_instance() method. 
     *
     * Dependencies can also have their own dependencies, of course, and this
     * method parses these via the parse_dependency() method.
     *
     * In a dependency list, an independent object (i.e. an object without
     * dependencies which can simply be instantiated) has a numeric key, while
     * a dependent object's name is its own key. The method tests whether or
     * not a given key is numeric, and then either gets the instance of the
     * object or parses the dependency further. */
    protected function readObject(array &$obj_map, $parent_obj = null) {
        foreach ($obj_map as $key => $value) {
            
            // key_test is true if key is numeric
            $key_test = $this->testKey($key);
            
            // if key is numeric, object name is the value
            // otherwise is the key
            $obj_name_ref = array(false => $key, true => $value);
            $obj_name = $obj_name_ref[$key_test];
            
            $name_check = $this->getRealObjName($obj_name);
            
            if (is_array($name_check)) {
                $obj_key = $name_check['real_name'];
                $obj_type = $name_check['obj_type'];
            }
            else {
                $obj_key = $obj_name;
                $obj_type = $obj_key;
            }
            
            unset($name_check);
            
            // if key is numeric, object has no dependencies and should
            // be parsed using getObjectInstance; otherwise use
            // parseDependency
            $obj = ($key_test === true) ?
                $this->getObjectInstance($obj_type, $parent_obj) :
                $this->parseDependency($obj_type, $value);

            $obj_list[$obj_key] = $obj;
            
            $this->updateSourcePool($obj_key, $obj);
        }
        return $obj_list;
    }
    
    /** \brief Converts an object's name to a user-defined key and an
     *         object type, if necessary.
     *
     * @param $obj_name Object name string from dependency map array.
     * 
     * If an object name contains an equals sign, this means the user has
     * defined it with a key other than simply the type of the object (the
     * default behavior). This method decodes this and returns an array
     * containing the user-defined key and the object's type. */
    protected function getRealObjName($obj_name) {
        $this->testObjectToInstantiate($obj_name);
        if (is_string($obj_name) && strpos($obj_name, '=')) {
            $names = array();
            $names['real_name'] = strtok($obj_name, '=');
            $names['obj_type'] = strtok('=');
            foreach ($names as $item) {
                $item = trim($item);
            }
            return $names;
        }
        else {
            return $obj_name;
        }
    }
    
    /** \brief Tests whether or not an array key is numeric.
     * @param $k Key from an array. Could be used with any value, really.
     *
     * Returns whether or not a value (in this object, generally an array key)
     * is numeric. */
    protected function testKey($k) {
        return(is_numeric($k));
    }
    
    /** \brief Routing method to either further parse a dependency or
               build it from the arguments provided.
     * @param $obj_name A string containing the name of the object to 
                        build or parse.
     * @param $dep_list The list of dependencies to parse. Can also be
                        a string containing the word 'none'.
     *
     * If the dependency list is not an array and instead is the string
     * 'none', then object $obj_name is assumed to have no arguments
     * and will be instantiated.
     *
     * Otherwise, the dependency list is parsed using read_object(), which
     * will return a list of objects that have been built. These objects
     * are then used via build_object_from_args() which passes them
     * into the constructor of $obj_name.
     */
    protected function parseDependency($obj_name, $dep_list) {
        if ($dep_list == 'none') {
            return $this->getObjectInstance($obj_name);
        }
        if (!is_array($dep_list) && $dep_list !== 'none') {
            $message = "Object $obj_name has an invalid dependency ".
                       "represented by $dep_list. Please correct the ".
                       "error in your object mapping.";
            throw new Exception($message);
        }
        $arg_list = $this->readObject($dep_list, $obj_name);
        $array_of_objs = strpos($obj_name, '_array');

        $obj_result = ($array_of_objs === false) ?
            $this->buildObjectFromArgs($obj_name, $arg_list) :
            $arg_list;
        
        return $obj_result;
    }
    
    /** \brief Instantiates an object by injecting its dependencies
               into its constructor.
     * @param $classname The name of the class to instantiate.
     * @param array &$args A list of dependencies to be passed in as
                           constructor arguments.
     * 
     * Instantiates a ReflectionClass version of $classname. This is
     * then used to determine (internally) the proper number of
     * arguments to $classname's constructor. $args should be an array
     * in the form (objectname => instantiated object). For constructor
     * purposes, this will be converted into a numeric-keyed array. */
    protected function buildObjectFromArgs($classname, array &$args) {      
        $ref_obj = new ReflectionClass($classname);
        $ctor_args = $this->getObjsOnly($args);
        $object = $ref_obj->newInstanceArgs($ctor_args);
        unset($ref_obj);
        return $object;    
    }
    
    protected function buildArrayOfObjects($arrayname, array &$args) {
        return array($arrayname => &$args);
    }
    
    /** \brief Converts an array into a numeric array using array_values().
     * @param array &$object_map An array in the format: (object name => 
                                 instantiated object)
     *
     * Uses array_values to convert an associative array into a numeric-keyed
     * array. */
    protected function getObjsOnly(array &$object_map) {
        return array_values($object_map);
    }
    
    /** \brief Routing method that either instantiates an object or 
               retrieves a reference to it from the object pool.
     * @param $obj_name Name of the object to instantiate or retrieve.
     * @param $parent_obj Optional; name of the parent of the object
                          being instantiated or retrieved.
     *
     * Uses call_user_func_array() and two arrays ($behavior and $args,
     * the former of which lists function names, the latter should be
     * self-explanatory) keyed to boolean false / true so as to avoid
     * duplicate/similar code. */
    protected function getObjectInstance($obj_name, $parent_obj = null) {
        //$this->testObjectToInstantiate($obj_name);
        $test = isset($this->source_pool[$obj_name]);
        
        $obj = ($test === true) ?
            $this->retrieveObject($obj_name, $parent_obj) :
            $this->instantiateObject($obj_name);

        return $obj;
    }
    
    /**
     * \brief Tests if object mappping has been read correctly.
     * @param $obj_name Should be a string containing object name.
     * 
     * If somehow the object name is read as an array rather than a string
     * (which can also be read as the string 'Array'), this method throws
     * an exception signalling the user to examine the object mapping. */
    protected function testObjectToInstantiate($obj_name) {
        if (is_array($obj_name) || $obj_name == 'Array') {
            $referring = $obj_name[key($obj_name)];
            $message = "You have an error in your object mapping. ".
                       "ShrubRoots is reading the line containing ".
                       "object $referring incorrectly.";
            throw new Exception($message);
        }

    }
    
    /** \brief Instantiates a new object with classname $obj_name.
     * @param $obj_name A string containing the name of the class to
                        instantiate.
     *
     * Checks the constructor arguments of class $obj_name; if there
     * are no constructor arguments required, an object of class
     * $obj_name is instantiated and returned. */

    protected function instantiateObject($obj_name) {
        $this->checkCtorParams($obj_name);
               
        return new $obj_name;
    }

    /** \brief Checks constructor arguments.
     * @param $obj_name The name of the class from which the constructor will
                        be analyzed.
     *
     * Makes a new ReflectionClass object of type $obj_name. The constructor
     * method (if any) is analyzed for the number of @parameters; if there
     * are any, an exception is thrown. */
    protected function checkCtorParams($obj_name) {       
        if (in_array('__construct', get_class_methods($obj_name))) {
         
            $ref_obj = new ReflectionClass($obj_name);
            $ref_method = $ref_obj->getMethod('__construct');
            $param_count = $ref_method->getNumberOfRequiredparameters();

            if ($param_count > 0) {
                $message = "Object $obj_name requires $param_count parameters.";
                throw new Exception($message);
            }

            unset($ref_obj);
        }
    }        

    /** \brief Returns a reference to or a clone of an instantiated object of
               type $obj_name.
     * @param $obj_name The name of the class to instantiate.
     * @param $parent_obj Optional string referring to the parent object of the
                          object being instantiated.
     *
     * Tests if the object to be referenced or cloned exists in the source
     * pool, then clones the object if it passes test_if_cloned() or
     * retrieves a reference if it doesn't. */
    protected function retrieveObject($obj_name, $parent_obj = null) {
        $source_obj = $this->source_pool[$obj_name];
        $clone_obj = clone $source_obj;
        if (!isset($source_obj)) {
            $message = "retrieveObject() should not be called for objects ".
                       "not yet instantiated. The offending object was ".
                       $obj_name.".";
            throw new Exception($message);
        }
        
        $should_clone = $this->testIfCloned($obj_name, 
                $source_obj, $parent_obj);
        $obj = ($should_clone === true) ?
            $clone_obj :
            $source_obj;
        return $obj;
    }
    
    /** \brief Tests if an object should be cloned.
     * @param $obj_name The name of the object to test for cloning.
     * @param $object An instantiated object.
     * @param $parent_obj Parent object of object $obj_name.
     *
     * Objects are only cloned if they are dependencies of another object;
     * therefore, if there is no parent object ($parent_obj) set, returns
     * false. Otherwise, if the object name is present as a key in the clone
     * list, the object is already instantiated (and is an object) and
     * the parent object is listed in the clone list, the method 
     * returns true. */
    protected function testIfCloned($obj_name, $object, $parent_obj = null) {
        if (!isset($parent_obj) || $parent_obj === null) {
            return false;
        }
        
        $behavior = array(false => 'cloneTestString',
                          true => 'cloneTestArray');
        
        $c_item = $this->clone_list[$obj_name];

        $clone_test = array_key_exists($obj_name, $this->clone_list) &&
                      is_object($object) &&
                      call_user_func(
                          array($this, $behavior[is_array($c_item)]),
                          $c_item, $parent_obj);
        
        return $clone_test;
    }
    
    /**
     * \brief Tests if an object (named by a string) should be cloned.
     * @param type $clone_parent String from the clone list detailing the
     *                           name of the parent object of the object to
     *                           be cloned.
     * @param type $parent_obj String containing the name of the parent object
     *                         of the item being instantiated.
     * 
     * Returns a boolean signalling if the clone item string is equal to the
     * parent object name string. The difference between the clone item string
     * and the parent object name string is that the former is obtained from
     * the clone list while the latter is obtained during parsing of the 
     * object mapping. Only objects that satisfy a dependency of another
     * object are cloned; this method ensures that the parent items match up.*/
    protected function cloneTestString($clone_parent, $parent_obj) {
        return $clone_parent === $parent_obj;
    }
    
    /**
     * \brief Tests if the parent of the object being instantiated is in
     *        the clone list.
     * @param array& $clone_list
     * @param type $parent_obj
     * 
     * Returns a boolean signalling if the parent of the object being
     * instantiated is found within the clone list array. 
     */
    protected function cloneTestArray(array $clone_list, $parent_obj) {
        return in_array($parent_obj, $clone_list);
    }
    
    /** \brief Public interface to the read_object() method.
     * @param array &$list The dependencies list to parse.
     *
     * Public interface to read_object(). Prior to executing, it merges
     * any internal object pool contents with those of the container's object 
     * pool. */
    public function demap(array &$list) {
        if (isset($this->source_pool['Container'])) {
            $built_objs = $this->source_pool['Container']->getBuiltItems();
            if (is_array($built_objs)) {
                $this->source_pool = 
                    array_merge($this->source_pool, $built_objs);
            }
        }
        
        return $this->readObject($list);
    }

    /** \brief Checks to see if an object exactly matches a particular
               object in the source pool.
     * @param $obj_name The key used to find the associated object in the
                        source pool.
     * @param $object The object that is being searched for in the source pool.
     *
     * Returns true if the the object referred to by key $obj_name in the
     * source pool is exactly equal to object $object. */
    protected function checkSourcePoolEmpty($obj_name) {
        if (array_key_exists($obj_name, $this->source_pool)) {
            $nullcheck = $this->source_pool[$obj_name] === null;
            $setcheck = !isset($this->source_pool[$obj_name]);
            return $nullcheck & $setcheck;
        }
        else return true;
    }
    
    /** \brief Sets an entry in the source pool.
     * @param $obj_name The key to set in the source pool.
     * @param $object The object to associate with key $obj_name.
     *
     * Keys $obj_name to $object in the source pool. */
    protected function setSourcePool($obj_name, $object) {
        $this->source_pool[$obj_name] = $object;
    }
    
    /** \brief Updates the source pool at key $obj_name with object
               $object.
     * @param $obj_name The key to set in the source pool.
     * @param $object The object to associate with key $obj_name.
     *
     * Updates the source pool at key $obj_name with a new / altered
     * object, provided the object is different.
     */
    protected function updateSourcePool($obj_name, $object) {
        if ($this->checkSourcePoolEmpty($obj_name)) {
            $this->setSourcePool($obj_name, $object);
        }
    }
   
}    
?>
