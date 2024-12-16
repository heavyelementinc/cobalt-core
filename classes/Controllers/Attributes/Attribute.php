<?php

namespace Controllers\Attributes;

use Routes\Options;

abstract class Attribute {
    protected Options $options;

    function set_route_details(Options $options) {
        $this->options = $options;
    }

    /**
     * @return void 
     */
    abstract function execute():void;
}