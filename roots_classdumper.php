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


if (!include_once(__DIR__.DIRECTORY_SEPARATOR.'spyc.php')) {
    trigger_error("spyc.php must be in the same directory as this file. Sorry.");
}

if (!defined('USER_FILE_EXT')) {
    define('USER_FILE_EXT', '.php');
}

/** \class Roots_ClassDumper
 * \brief Produces a serialized mapping of paths to source code files.
 *
 * This entire class is meant as static (even though PHP doesn't have
 * any such thing as a true static class), in that it's not meant to
 * be instantiated. This script is simply meant as a tool for those
 * using ShrubRoots.
 *
 * In the future, this class may be refactored into static and
 * non-static functions, so that it can be used within ShrubRoots itself
 * or any other application, but it's just static for now. */    
class Roots_ClassDumper
{

    /** \brief Creates and writes to disk a serialized class map.
     *
     * Obtains command-line arguments (if any) listing the 
     * directories to map and/or the serialization type, creates
     * the class map, then serializes it and writes it to disk. 
     * If there are no command-line arguments, type is set to YAML. */
    public static function map() {
        if (count($argv) > 1) {            
            $args = self::getArguments();
            $options = self::convertArgsToOptions($args);
            $class_map = self::routeUsingOpts($options);
        }
        else {
            $class_map = self::checkBaseDir();
            $options = self::convertArgsToOptions();
        }        
        
        $ser_array = self::serializeClassMap($class_map, $options['type']);
        self::writeSerialized($ser_array);
        print_r($class_map);
        
    }
    
    /** \brief Parses either working dir or dirs listed in arguments. 
     * @param array &$opts Array of options.
     *
     * If there are directories set in the options, parse these;
     * otherwise, use the working directory. */
    private function routeUsingOpts(array &$opts) {
        $type = $opts['type'];
        $dirs = $opts['dirs'];
        
        $dirs !== null ?
            self::checkMultipleDirs($dirs) :
            self::checkBaseDir();
    }

    /** \brief Get argument list from command line.
     *
     * Gets arguments from command line, omitting the initial element,
     * which is just the filename of the current script. */
    private function getArguments() {
        return array_slice($argv, 1);
    }

    /** \brief Converts command-line arguments into options.
     * @param array &$args Argument list.
     * 
     * Makes a string out of the argument array, then checks the string
     * for a '-h' argument. If this is present, the help message is
     * displayed and execution is terminated.
     *
     * The argument string is validated, parsed, and broken into an array
     * of argument strings, which are converted into options readable by
     * the script. */
    private function convertArgsToOptions(array &$args) {
        $argstring = implode(' ', $args);
        if (stripos($argstring, '-h')) {
            self::displayHelpMessage();
        }
        $pos_arr = self::makePosArr($argstring);
        $pos_arr = self::validatePositions($pos_arr);
        
        $str_arr = self::getStringArr($argstring, $pos_arr);
        self::validateStrings($str_arr);
        $options = self::generateOptions($str_arr);
        return $options;
    }

    /** \brief Breaks a string containing all arguments into an array.
     * @param $argstring The string containing all command-line arguments.
     * @param array &$pos_arr Contains starting positions of each argument.
     *
     * Breaks an argument string (a string version of all command-line
     * arguments) into an array of strings, each corresponding to an
     * argument and its sub-argument(s). */
    private function getStringArr($argstring, array &$pos_arr) {
        $arr_length = count($pos_arr);
        for ($i = 0; $i < $arr_length; $i++) {
            $str_length = self::getLength($pos_arr, $i, $arr_length);
            $str_arr[] = substr($argstring, $pos_arr[$i], $str_length);
        }
        return $str_arr;
    }

    /** \brief Tests validity of argument strings.
     * @param array &$string_arr Array of argument strings.
     *
     * Rejects argument strings if they do not begin with a '-' character,
     * if they do not contain either 'd' or 't' as arguments, and/or if they
     * do not contain at least 3 characters as a subargument. */
    private function validate_strings(array &$string_arr) {
        foreach ($string_arr as $string) {
            if (self::getChar($string, 0) !== '-' || 
                self::testArgExists($string) === false ||
                self::testSubArg($string) === false) {
                    trigger_error("Malformed string $string found via
                                   validate_strings.");
            }
        }
    }
    
    /** \brief Returns subarguments keyed to their option types.
     * @param array &$string_arr Array of argument strings.
     *
     * Obtains the option type (can be only 'd' or 't' in this version)
     * from an argument string, then keys this to the subargument
     * contained in the string. Returns an array of options. */
    private function generate_options(array &$string_arr = null) {
        $options = array('directories' => null,
                         'type' => 'yaml');
        $types = array('d' => 'directories',
                       't' => 'types');
        
        if ($string_arr === null) {
            return $options;
        }
                       
        foreach ($string_arr as $string) {
            // removes initial '-'
            $string = substr($string, 1);
            
            $opttype = self::getChar($string, 0);
            $string = substr($string, 2);
            
            $opttype == 'd' ?
                $options[$types[$opttype]] = explode(' ', $string) :
                $options[$types[$opttype]] = $string;
        }
        
        if ($options['type'] != 'yaml' || $options['type'] != 'xml') {
        	$sertype = $options['type'];
            throw new Exception("Serialization type $sertype not supported.");
        }
        return $options;
    }
    
    /** \brief Returns a single character from a string.
     * @param $str The string to parse.
     * @param $pos The position to obtain the character from.
     *
     * Uses substr() to retrieve a single character out of a string. */
    private function getChar($str, $pos) {
        return substr($str, $pos, 1);
    }
    
    /** \brief Tests for supported argument types.
     * @param $string The argument string to test.
     * 
     * If the second character of the string is not 'd' or 't', 
     * return false. */
    private function testArgExists($string) {
        $arg_key = self::getChar($string, 1);
        $argtest = ($arg_key == 'd' || $arg_key == 't');
        return $argtest;
    }

    /** \brief Tests format of subarguments.
     * @param $string The argument string to test.
     * 
     * If the length of the string is less than 3, or
     * it contains more than two consecutive spaces, 
     * return false. */
    private function testSubArg($string) {
        if (strlen($string) >= 3) {
            $subarg = substr($string, 3);
            $pattern = "+[  ]{2, }+";
            if (preg_match($pattern, $string)) {
                return false;
            }
            return true;
        }
        
        return false;

    }
    
    /** \brief Gets the position of an argument via the '-' character.
     * @param $argstring A string containing all command-line arguments.
     * @param $offset Where to start searching for an argument. 
     *
     * Gets the position of the first '-' character found starting at
     * $offset. To protect against splitting the string on '-' characters
     * found in subarguments, for instance in a directory listing containing
     * the text 'c:\php\a-a-a\', the method recursively searches for the 
     * next '-' character if there is no space before a '-' character. */
    private function getArgPosition($argstring, $offset) {
        $position = stripos($argstring, '-', $offset);
        if ($position !== false) {
            if (substr($argstring, $position - 1, 1) !== ' ') {
                $position = self::getArgPosition($argstring, $position);
            }
        }
        return $position; 
    }
    
    /** \brief Obtains an array of all argument positions in a string.
     * @param $argstring A string containing all command-line arguments.
     * 
     * Iteratively uses the get_arg_position method to obtain all
     * argument positions within an argument string. */
    private function makePosArr($argstring) {
        $offset = 0;
        $pos_arr = array();
        while (1) {
            $argpos = self::getArgPosition($argstring, $offset);
            if ($argpos === false) {
                break;
            }
            
            $pos_arr[] = $argpos;
            $offset = $argpos + 1;
        }
        
        return $pos_arr;
    }
    
    /** \brief Paranoid method checking if positions are truly numeric.
     * @param array $pos_arr
     *
     * Checks each position to see if it is numeric. A special case is 
     * made for the string value '0', as this will evaluate to numeric
     * zero, which is what intval() uses to indicate failure.
     *
     * It is not likely that the positions will ever be non-numeric, but
     * this is here just in case. */
    private function validatePositions(array $pos_arr) {
        foreach ($pos_arr as $key => $position) {
            if (!is_numeric($position) && $position != '0') {
                $position = intval($position);
                
                // return value of 0 from intval = failure
                if ($position == 0) {
                    trigger_error("Position array[$key] is not numeric.");
                }
            }
            if ($position == '0') {
                $position = 0;
            }
        }
        return $pos_arr;
    }

    /** \brief Calculates the length of an argument string from its position. 
     * @param array &$pos_arr The position array.
     * @param $arr_offset The key of the position array to look at.
     * @param $arr_length The total length of the array.
     *
     * Given two consecutive positions, calculates the distance between them.
     * If there is no subsequent position (i.e. the key of the position array
     * is the highest in the array (checked against the arr_length variable),
     * the length is set to null. The length is later passed to substr(), so
     * a null value will indicate to substr() that it should simply read to
     * the end of the string. */
    private function getLength(array &$pos_arr, $arr_offset, $arr_length) {
        $up_bound = $arr_offset + 1;
        
        $lower = $pos_arr[$arr_offset];
        
        $up_bound >= $arr_length ? 
        	null :
            $pos_arr[$arr_offset + 1];
        
        return ($upper - $lower);
    }  

    /** \brief Turns a class map array into a serialized form, given by $type.
     *  As of version 0.991, XML is not yet supported.
     * @param array &$class_map The class map array to serialize.
     * @param $type The type of serialization to perform.
     *
     * Serializes a class map array in either XML (forthcoming) 
     * or YAML format. */
    private function serializeClassMap(array &$class_map, $type) {
        switch($type) {
            case "yaml" :
                return self::doYaml($class_map);
            case "xml" :
                //return self::do_xml($class_map);
                die("XML is not supported yet.");
        }
    }
    
    /** \brief Serializes via YAML.
     * @param array &$class_map The class map array to serialize. 
     * 
     * Uses <a href="http://spyc.sourceforge.net">Spyc</a> to perform
     * the YAML serialization. */
    private function doYaml(array &$class_map) {
        return Spyc::YAMLDump($class_map);
    }
    
    /** \brief Will eventually serialize via XML. Right now, refers to do_yaml.
     * @param array &$class_map The class map array to serialize.
     *
     * At the moment, is a duplicate of do_yaml. DO NOT USE. */
    private function doXml(array &$class_map) {
        echo '';
    }
    
    /** \brief Writes a serialized class map to disk.
     * @param array &$serial_arr The serialized class map.
     * @param $type The type of serialization used. 
     * 
     * Writes a serialized class map to disk.
     * Currently does not support XML. */
    private function writeSerialized(array &$serial_arr, $type) {
        $filename = __DIR__.DIRECTORY_SEPARATOR."classmap.".$type;
        $fp = fopen($filename, 'wb');
        
        if (!$fp) {
            trigger_error("Could not open $filename!\n\n");
        }
        fwrite($fp, $serial_arr, strlen($serial_arr));
        fclose($fp);
    }    
    
    /** \brief Parses only the current working directory into a class map.
     *
     * Uses the check_dir_recursive() method to parse the current working 
     * directory to create a class map. */
    private function checkBaseDir() {    
        $path = __DIR__;        
        return self::checkDirRecursive($path);
    }

    /** \brief Parses a list of directories into a class map. 
     * @param array &$dirs List of directories to parse. 
     *
     * Parses all directories listed in the method argument
     * into a class map via the check_dir_recursive() method. */
    private function useMultipleDirs(array &$dirs) {
        $class_map = array();
        if (count($dirs) == 1) {
            return self::checkDirRecursive($dirs[key($dirs)]);
        }
        foreach ($dirs as $dir) {
            $class_map = array_merge($class_map, self::checkDirRecursive($dir));
        }
        return $class_map;
    }
    
    /** \brief Recursively parses directories into a class map.
     * @param $path The directory to parse recursively.
     * @param array &$class_map Class map, used in recursive iterations. 
     * @param $parent The parent directory. 
     *
     * Reads all files from $path. Forms two lists, one for directories
     * encountered, and another for the files bearing the desired file
     * extension (.php by default). Keys the path to the file list, then
     * recursively traverses the directories in the directory list to
     * map all files encountered in them as well. */
    private function checkDirRecursive($path, array &$class_map = null) {
        if (!is_readable($path)) {
            trigger_error("Unreadable path or non-directory passed to
                           check_dir_recursive().");
        }
        
        chdir($path);
        
        $files = scandir($path);
        $all_dirs = self::testFiles($files, 'dir_test');
        $dir_list = self::keepOnlySubdirs($all_dirs);
        
        $valid_files = self::obtainValidFiles($files, $all_dirs);
        $valid_files = self::cleanExtensions($valid_files);
        
        $class_map[$path] = &$valid_files;
        
        foreach ($dir_list as $dir_path) {
            $dir_path_qual = $path.DIRECTORY_SEPARATOR.$dir_path;
            self::checkDirRecursive($dir_path_qual, $class_map);
        }
        
        return $class_map;
    }

    /** \brief Filters out directories and undesired files.
     * @param array &$files List of files to filter.
     * @param array &$dir_list Directories that should be excluded.
     *
     * Filters out directories and returns only files ending in the
     * USER_FILE_EXT extension. */
    private function obtainValidFiles(array &$files, array &$dir_list) {
        $files = self::filterDirs($files, $dir_list);
        return self::testFiles($files, 'file_test');
    }
    
    /** \brief Removes file extensions from files in a file list.
     * @param array &$files A list of filenames.
     * 
     * Strips USER_FILE_EXT (user-set file extension, .php by default)
     * from filenames, so the ShrubRoots autoload function can search
     * for them in this list without having to alter the classname. */
    private function cleanExtensions(array &$files) {
        foreach ($files as $file) {
            $extpos = stripos($file, USER_FILE_EXT);
            $file = substr($file, 0, $extpos);
        }
        return $files;
    }
    
    /** \brief Strips non-subdirectories from list of files.
     * @param array &$files A list of filenames.
     *
     * Removes current working directory ('.') and parent directory
     * ('..') listings from a list of filenames, leaving only subdirectories
     * and files. */
    private function keepOnlySubdirs(array &$files) {        
        $removedirs = array('.', '..');
        return self::filterDirs($files, $removedirs);
    }

    /** \brief Abstract interface to test files via different criteria. 
     * @param array &$list List of files to test. 
     * @param array &$callback Array containing user function info.
     *
     * The format of $callback must be: ('self' => 'methodname').
     *
     * Uses call_user_func so as to allow this method to be a single
     * interface for the file_test and dir_test methods. */
    private function testFiles(array &$list, array &$callback) {       
        foreach ($list as $file) {
            $test = call_user_func(array("self", $callback), $file);
            if ($test != FALSE) {
                $keep_list[] = $file;
            }
        }
        return $keep_list;
    }

    /** \brief Tests files for validity based on their extension.
     * @param $file A filename to test.
     *
     * If a file does not end in the user-defined file extension 
     * (USER_FILE_EXT), returns false. */
    private function fileTest($file) {
        $test_result = strripos($file, USER_FILE_EXT);
        return $test_result;
    }

    /** \brief Tests if a filename refers to a directory.
     * @param $file A filename to test.
     *
     * If a filename does not refer to a directory, return false.*/
    private function dirTest($file) {
        $test_result = is_dir($file);
        return $test_result;
    }

    /** \brief Filters files out of a list based on criteria.
     * @param array &$list A list of filenames. 
     * @param array &$criteria Filenames to filter out of list. 
     *
     * Removes any items found in $criteria from file list $list.*/    
    private function filterDirs(array &$list, array &$criteria) {    
        foreach ($criteria as $remove_item) {
            unset($list[array_search($remove_item, $list)]);
        }
        return $list;
    }

    /** \brief Displays a help message.
     *
     * Displays the help message, then ends execution of script.*/
    private function displayHelpMessage() {
        $message = "ClassDumper help:\n
                   -h : displays this help message\n
                   -f (directory 1 directory 2 ... directory n) : 
                    parses those directories\n
                   -t (xml / yaml) : selects type of serialization\n
                   (no args) : parses working directory in YAML format\n";

        echo $message;
        die('ShrubRoots ClassDumper v0.991');
    }    
    
}   

?>
