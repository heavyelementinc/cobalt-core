<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;

class Anchor extends SchemaResult {
    public function setText(string $value):void {
        $this->schema['text'] = $value;
    }

    public function setHref(string $value):void {
        $this->value = $value;
        $this->originalValue = $value;
    }

    public function setClass(string $value):void {
        $this->schema['class'] = $value;
    }

    public function setDisabled(bool $value):void {
        $this->schema['disabled'] = $value;
    }
    
    function __toString(): string {
        $href = $this->getValue();
        
        $text = $this->getDirective('text');
        if(!$text) $text = $href;
        
        $class = $this->getDirective('class');
        if($class) $class = " class=\"$class\"";
        
        $disabled = $this->getDirective('disabled');
        if($disabled) $disabled = " disabled=\"disabled\"";
        return "<a href=\"$href\"$class"."$disabled>$text</a>";
    }
}