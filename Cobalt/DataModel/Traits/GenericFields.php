<?php

namespace Cobalt\DataModel\Traits;

use Cobalt\HTML\Field;

trait GenericFields {
    function field():Field {
        $field = new Field($this);
        return $field;
    }
    
    function getFieldInnerHTMLBefore():string {
        return "";
    }

    function getFieldInnerHTMLAfter():string {
        return "";
    }
}