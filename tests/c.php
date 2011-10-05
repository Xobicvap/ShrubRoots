<?php

class C
{

    public $a_obj;
    public $d_obj;
    

    public function __construct($a_obj, $d_obj) {
        $this->a_obj = $a_obj;
        $this->d_obj = $d_obj;
    }
    
    public function dump() {
        return array($this->a_obj, $this->d_obj);
        
    }
    
    public function changeD() {
        $this->d_obj->changeA();
    }
    
    public function showD() {
        return $this->d_obj->showA();
    }
    
}

?>