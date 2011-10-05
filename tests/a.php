<?php

class A
{

    public $a;

    public function __construct() {
        $this->a = 'FOOOOOOB';
    }
    
    public function getFoob() {
        return $this->a;
    }
    
    public function setDifferentFoob() {
        $this->a = 'foooooooB';
    }
        
}

?>