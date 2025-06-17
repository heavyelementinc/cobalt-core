<?php

/**
 * CLI Handler
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2022 - Heavy Element, Inc.
 */

namespace Handlers;

class CliHandler extends RequestHandler {
    
    function __construct() {
        
    }


    public function _stage_init($context_meta):void {
        
    }

    public function _stage_route_discovered(string $route, array $directives, bool $isOptions):bool {
        return false;
    }

    public function _stage_execute($router_result):void {
        
    }

    public function _stage_output($context_output):mixed {
        return null;
    }

    public function _public_exception_handler($e):mixed {
        return null;
    }

    function request_validation($directives) {

    }

    function __destruct() {
        // if ($this->_stage_bootstrap['_stage_output'] === true) return;
        // $this->_stage_output();
    }

}
