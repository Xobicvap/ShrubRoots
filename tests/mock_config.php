<?php

require_once dirname(__FILE__) . '/../../../../../../../php/www/srtest/shrub_iconfig.php';

/**
 * Mock config object.
 *
 * @author Rusty Hamilton
 */
class Mock_Config implements Shrub_IConfig
{

    protected $conf_arr;

    public function retrieveConfigArray() {
        if ($this->conf_arr === null || !isset($this->conf_arr)) {
            $this->conf_arr = array('autoload_file_extension' => '.php',
                              'seedfile' => 'a.php',
                              'parser' => 'Mock_Parser',
                              'object_builder' => 'Mock_Builder',
                              'object_demapper' => 'Mock_Demapper',
                              'result' => 'fabzx',
                              'prebuild_name' => 'prebuild',
                              'initial_obj_demap_link' => 'fabzx');
        }
        return $this->conf_arr;
    }
    
    public function retrieveConfigPath() {
        return __DIR__.DIRECTORY_SEPARATOR;
    }
        
    public function changeConfigArray($newconf) {
        $this->conf_arr = $newconf;
    }    
    
}


?>
