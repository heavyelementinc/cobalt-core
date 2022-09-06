<?php

namespace Cobalt\Settings;

use Exception;

class Manager {
    
    function __construct($values) {
        if(gettype($values) !== "array") throw new Exception("Values are not an array");
        $this->__values = $values;
    }

    private $__values = [];

    function __get($name) {
        if(!isset($this->__values[$name])) throw new Exception ("$name is not a recognized setting");

        $instantiable = "\\Cobalt\\Settings\\Definitions\\$name";

        return new $instantiable($this->__values[$name], $this->__values);
    }

    function __set($name, $value) {
        
    }
}