<?php

class ComplexObject_A
{
    private $variable;
    
    public function __construct($var) {
        $this->variable = $var;
    }
    
    public function square_var() {
        $d = $this->variable * $this->variable;
        return $d;
    }
}


?>
