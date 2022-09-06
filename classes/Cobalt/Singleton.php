<?php

namespace Cobalt;

abstract class Singleton {
    function __construct($ref, $allowOverwriting = false) {
        $singletonName = $this->singletonName();

        if(isset($GLOBALS[$singletonName]) && $allowOverwriting === false) throw new \Exception("An instance of $singletonName already exists. Aborting.");

        $GLOBALS[$singletonName] = $ref;
    }

    abstract function singletonName():string;
}