<?php

namespace Cobalt\Maps;

trait DeepFind {
    function __nestedFind($name) {
        $find = func_get_args();
        array_shift($args);
        
    }
}