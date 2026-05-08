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
        $this->define_valid(function () {
            return $GLOBALS['HTML_ROUTE_CACHE'];
        }, 'valid');
    }

}