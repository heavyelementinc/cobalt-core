<?php

namespace Cobalt\Routing\Interfaces;

use Stringable;

interface ExecutionResult {
    function getBodyView(array $vars = []):string;
    function getBodyTemplate():?string;
    function setBodyTemplate(?string $bodyTemplate):void;

    /**
     * Typically we want to assign a template result
     * @param mixed $result 
     * @return void 
     */
    function setControllerResult(string $target, string|Stringable $result):void;
    function getControllerResult():string;
    
}
