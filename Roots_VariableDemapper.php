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

/** \class Roots_VariablesDemapper
 *  Creates variables from a variables mapping.
 *
 * Roots_VariablesDemapper is used to generate any scalar values, arrays,
 * or strings an application may need at runtime. It was originally
 * called a constants demapper due to constants also being declared
 * at runtime, but this was found to be an inaccurate name.
 *
 * As of version 0.99, the design of this module is very rudimentary.
 * Ideally, it would be useful to have multiple objects taking the
 * place of the multiple if statements. A Command pattern might be
 * applicable here.
 *
 * Of course, if the Command pattern is implemented, it would be
 * necessary to use not just the ObjectDemapper but the FactoryDemapper
 * as well so as to generate all desired Command objects at once.
 *
 * Regardless, the class does do what it is supposed to, and so I will leave
 * implementing extensibility into this class for a future version. */
class Roots_VariableDemapper implements Roots_IDemappable, Roots_IObtaining
{
    
    protected $variables = array();
    protected $parser;
    /** \brief Roots_VariableDemapper constructor.
     * 
     * @param Roots_IExtract $parser Parser for extracting data from 
     *                               parsable files.
     * Loads an optional parser into the Roots_VariableDemapper object.
     */
    public function __construct(Roots_IExtract $parser = null) {
        $this->parser = $parser;
    }
    
    /** \brief Public interface method; creates variables from a list.
     * @param array &$list The list to demap into variables.
     * 
     * Currently supports: scalars (integer or float), strings, arrays,
     * include files, file handles, and text files (via file_get_contents).*/
    public function demap(array &$list ) {
        $variables = array();
        foreach ($list as $name=>$item) {
            $arr = $this->computeVariables($name, $item);
            $variables[key($arr)] = current($arr);
        }
        
        $this->variables = array_merge($this->variables, $variables);

        return $variables;
    }
    
    /** \brief  Determines the type of the variable being demapped and returns.
     * @param $name A key to which the variable's value can be mapped.
     * @param array &$item The variable mapping.
     *
     * Determines type of variable to initialize, then does so. Throws
     * exceptions if type and value are not set in variable mapping, or if
     * type does not match any of the supported types. */
    protected function computeVariables($name, array &$item) {
        if (!isset($name) ||
            !is_string($name)) {
        	throw new Exception("Each variable mapping must have a name.");
        }
        
        if (!isset($item['type']) || 
            !isset($item['value'])) {
            throw new Exception("Variable mappings must have a ". 
                                 "type and value.");
        }
        
        $type = $item['type'];
        $value = $item['value'];
        
        // int and float types
        if ($type == 'int' || $type == 'float') {
            settype($value, $type);
            if (!is_numeric($value)) {
                throw new Exception("$value could not be converted to $type.");
            }
            return array($name => $value);
        }
        
        if ($type == 'string') {
            $check_str = is_string($value);
            $return_array = ($check_str) ?
                array($name => $value) :
                array($name => "$value");
            return $return_array;
        }
        
        if ($type == 'array') {
            return array($name => $this->readArrayType($value));
        }
        
        if (stristr($type, 'file')) {
            $this->testDir($value);
            
            if ($type == 'text_file') {
                return array($name => file_get_contents($value));
            }

            if ($type == 'include_file') {
                require $value;
                return array($name => 'included');
            }

            if ($type == 'file_handle') {
                $fp = fopen($value, "r");
                if (!$fp) {
                    throw new Exception("Could not open file $value.");
                }
                return array($name => $fp);
            }

            if ($type == 'parsable_file') {
                $parse_file = file_get_contents($value);
                $parsed_arr = $this->parser->extract($parse_file);
                return array($name => $parsed_arr);
            }
            
        }

        unset($item[$type]); 
        throw new Exception("No supported types found in variable mapping!");
        
    }

    /** Checks if a filename contains a valid path.
     *
     * @param $filename Filename to check, supposedly including path as well.  
     * 
     * The VariableDemapper will not load files requiring any sort of complex
     * lookup logic. You MUST specify the full path to the file to load.
     * This method checks to ensure you have done so, and that the path you
     * specified is valid and readable.
     * 
     * As far as why the VariableDemapper will not accept files without
     * paths at all (i.e. you assume they're in the same directory as
     * ShrubRoots), number one, you shouldn't be stuffing things in the
     * ShrubRoots directory (use your own!) and number two, having the
     * VariableDemapper just read in the contents of arbitrary files using
     * relative paths is a bit of a security risk, at least in theory. */
    protected function testDir($filename) {
        $complete_path_test = strrpos($filename, DIRECTORY_SEPARATOR);
        if ($complete_path_test === false) {
            throw new Exception("$filename must have a complete path.");
        }
        $path = substr($filename, 0, $complete_path_test);
        if (!is_readable($path)) {
            throw new Exception("$filename does not exist in readable path.");
        }
    }
    
    /** \brief Returns an appropriately-mapped array.
     *
     * @param array $array_values Corresponds to the "value" field of an 
     *                            array being demapped.
     * When the computeVariables method is parsing an array type, it sends
     * the array values to this method. This method takes these mapped values
     * and arranges them such that each item in the array desired refers to
     * either an existing or new variable.
     */
    protected function readArrayType(array &$array_values) {
        foreach ($array_values as $name => $var) {
            if (is_array($var)) {
                $temp_arr = $this->computeVariables($name, $var);
                $return_array[$name] = current($temp_arr);
            }
            else if (array_key_exists($var, $this->variables)) {
                $return_array[$name] = $this->variables[$var];
            }
        }
        return $return_array;
    }
    
    /** \brief Returns all variables built using this demapper.
     *
     * Returns the list of variables produced after demapping a variables list.
     */    
    public function getBuiltItems() {
        return $this->variables;
    }
    
         
}

?>
