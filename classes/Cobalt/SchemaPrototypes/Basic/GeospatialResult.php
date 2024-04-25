<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\MapResult;

class GeoPointResult extends MapResult {

    function longitude() {    
        return $this->value[0];
    }

    function latitude() {
        return $this->value[1];
    }

}