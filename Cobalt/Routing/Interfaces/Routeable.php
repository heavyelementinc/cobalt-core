<?php

namespace Cobalt\Routing\Interfaces;

use Closure;
use Iterator;
use Routes\Options;

/**
 * @package Cobalt\Routing\Interfaces
 */
interface Routeable {

    function toArray():array;

    static function fromArray(array $array): Routeable;
    
}
