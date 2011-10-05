<?php

class D
{

    public $a_obj;
    public $b_obj;
    
    public function __construct(A $a, B $b) {
        $this->a_obj = $a;
        $this->b_obj = $b;
    }
    
    public function dump() {
        return array($this->a_obj, $this->b_obj);
    }
    
    public function changeA() {
        $this->a_obj->setDifferentFoob();
    }
    
    public function showA() {
        return $this->a_obj->getFoob();
    }
    
}

?>