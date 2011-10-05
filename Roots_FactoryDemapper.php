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

/** \class Roots_FactoryDemapper
 *  Demaps a list detailing how to build and use factories.
 *
 * A factory mapping list is comprised of two required lists and
 * one optional list. 
 *
 * The 'builds' list dictates what objects are needed by a particular
 * factory. The 'assignments' list maps an object name to a particular
 * factory. Finally, the 'sequence' list is optional but dictates the
 * order in which to build factory objects at run-time.
 * The assignments and builds lists are necessary so that client code, 
 * when requesting a particular object, needs to do nothing more than 
 * simply pass on the name of the needed object; the Roots_Container 
 * object uses the mapped factory to build the object, keeping the client
 * unaware of which factory is actually used.
 *
 * It is important to remember that while the usual use for factories is
 * to generate objects during runtime, ShrubRoots also allows for the 
 * generation of complex objects <em>at</em> runtime. In fact, this is
 * one of the selling points of ShrubRoots; it tries to build as much
 * as possible at run-time, so that everything built can be cached
 * before an application even starts. This is about as close to
 * pre-compilation as you can get without actually compiling PHP bytecode
 * a la the Facebook group's HipHop project. 
 **/
class Roots_FactoryDemapper implements Roots_IDemappable
{

    private $obj_demap;
    
    /** \brief Roots_FactoryDemapper constructor.
     * @param Roots_IDemappable $obj_demap Roots_ObjectDemapper containing a
        reference to the Roots_Container object.
     *
     * The Roots_ObjectDemapper used here contains a reference to the 
     * Roots_Container, so that it can inject this reference into any objects
     * it builds that need it.*/
    public function __construct(Roots_IDemappable $obj_demap) {
        $this->obj_demap = $obj_demap;
    }
    
    /** \brief Builds factories based on info from a factory demapping list.
     * @param array &$list A factory demapping list.
     *
     * Reads the factories demapping list, from which the method builds
     * factories, assigns objects to them, and (optionally) specifies the
     * order in which to build them at runtime. */
    public function demap(array &$list) {
        if (!isset($list['assignments']) ||
                !isset($list['builds'])) {
            throw new Exception("Factory maps require both assignment lists ".
                    "and build lists.");
        }
        
        $assigns = $list['assignments'];
        $builds = $list['builds'];
        $sequence = $list['sequence'];
        $factory_mapping = $this->buildFactories($builds);
        $factory_list = $this->mapFactories($assigns, $factory_mapping);
       
        $factories = array('factories' => &$factory_list);
        if (isset($sequence) && !empty($sequence)) {
            $factories['sequence'] = $sequence;
        }        
        
        return $factories;
    }    

    /** \brief Builds factory objects using a Roots_ObjectDemapper.
     * @param array &$build_list The builds list used to tell the factory
     * demapper object how to build factories.
     *
     * Uses the Roots_ObjectDemapper reference injected into this class to
     * build factory objects via a builds list. */
    private function buildFactories(array &$build_list) {
        return $this->obj_demap->demap($build_list);
    }

    /** \brief Maps an object name to a particular instantiated factory object.
     * @param array &$assign_list The assignments list dictating which object name
        to assign where.
     * @param array &$factory_obj_map A list of already-built factory objects. 
     *
     * Maps the object names found in the assignments list to the corresponding
     * concrete factory objects found in the factory object map.*/
    private function mapFactories(array &$assign_list, 
                                  array &$factory_obj_map) {
        foreach ($assign_list as $obj_name => $fact_name) {
            $fact_obj = $factory_obj_map[$fact_name];
            $fact_assigns[$obj_name] = $fact_obj;
        }
        return $fact_assigns;
    }
}

?>
