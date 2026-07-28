<?php

namespace Cobalt\Model\Directives\Abstracts;

use Cobalt\Model\Types\MixedType;

abstract class AbstractDirective {
    protected ?MixedType $_reference = null;
    
    function isReady():bool {
        return $this->_reference == null;
    }

    function getReference(): MixedType {
        return $this->_reference;
    }

    function setReference(MixedType $value):void {
        $this->_reference = $value;
    }

    abstract function getValue():mixed;
}