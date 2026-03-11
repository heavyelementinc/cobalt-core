<?php

namespace Cobalt\Model\Types;

use Handlers\WebHandler;

class ArrayOfRoutesType extends ArrayType {

    public function initDirectives(): array {
        return [
            'input_tag' => 'input-array'
        ];
    }

    public function finalInitialization():void {
        $valid = [];
        foreach(__APP_SETTINGS__['context_prefixes']['directives']['prepend'] as $prefix => $options) {
            if(new $options['processor'] instanceof WebHandler === false) continue;
            
        }
        foreach($GLOBALS['ROUTE_TABLE'] as $context => $methods) {
            foreach($methods['get'] as $regex => $arr) {
                $valid[$regex] = $arr['real_path'];
            }
        }
        $this->define_valid($valid, 'valid');
    }

}