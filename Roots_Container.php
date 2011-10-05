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

/** \class Roots_Container
 * \brief Main container object for ShrubRoots system.
 *
 * The Roots_Container class is the heart of ShrubRoots; it provides the
 * actual container from which to obtain objects, and the ShrubRoots internals
 * involved in making said objects are contained within it as well. */
class Roots_Container implements Roots_IContaining
{
    /** Array of objects built and contained within this object.  */
    protected $objects;
    
    /** Object builder object. */
    protected $obj_builder;
    
    /** \brief Roots_Container constructor.
     *
     * @param Roots_IBuilding $obj_builder Object builder object.
     * 
     * Sets an object builder and calls the object builder's 
     * loadObjects method. */
    public function __construct(Roots_IBuilding $obj_builder) {
        $this->obj_builder = $obj_builder;
        $this->loadObjects();
    }
    
    /** \brief Builds objects specified by dependency mapping.
     * 
     * If objects have not yet been built, builds objects using the
     * object builder. */
    protected function loadObjects() {
        if (!isset($this->objects)) {
            $this->obj_builder->toggle_autoload('on');
            $this->objects = $this->obj_builder->buildObjects();
            $this->obj_builder->toggle_autoload('off');
        }
    }
    
    /** \brief Retrieves an object or variable from the container.
     *
     * @param $obj_name Name of object to retrieve from container.
     * 
     * Retrieves object/variable $obj_name from the container.
     */
    public function retrieveItem($obj_name) {
        if (array_key_exists($obj_name, $this->objects) &&
            is_string($obj_name)) {
            return $this->objects[$obj_name];
        }
        else {
            throw new Exception("Object $obj_name not present in container.");
        }
    }
    
    /** \brief Retrieves all previously built items inside the container.
     *
     * Retrieves all objects/variables built by the container.
     */
    public function getBuiltItems() {
        return $this->objects;
    }
    
    /** \brief Builds an object using any factory objects previously built.
     *
     * @param $obj_name Concrete type of object to build via factory.
     * @param array $params Any parameters needed to build object.
     * 
     * Uses the object builder and the factory object list to build object type 
     * $obj_name using optional parameters. */
    public function retrieveFactoryBuiltObject($obj_name, 
                                               array &$params = null) {
        if (array_key_exists('factories', $this->objects)) {
            $factories = $this->objects['factories'];
            $obj = $this->obj_builder->buildFromFactory($factories, 
                                                        $obj_name, $params);
            return $obj;
        }
        else {
            return false;
        }
    }
    
}

?>
