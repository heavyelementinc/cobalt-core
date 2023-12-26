<?php

namespace Cobalt;

class SubMap extends PersistanceMap {
    private $__stored;

    function __set_schema(array $value):void {
        $this->__stored = $value;
    }

    function __get_schema(): array {
        $var = $this->__stored;
        unset($this->_stored);
        return $var;
    }
}