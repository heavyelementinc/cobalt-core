<?php

/**
 * CLI Handler
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2022 - Heavy Element, Inc.
 */

namespace Handlers;

class CliHandler implements RequestHandler {
    
    function __construct() {
        
    }


    public function _stage_init($context_meta) {
        
    }

    public function _stage_route_discovered($route, $directives) {
        
    }

    public function _stage_execute($router_result) {
        
    }

    public function _stage_output() {
        
    }

    public function _public_exception_handler($e) {
        
    }

    function request_validation($directives) {

    }

    function __destruct() {
        // if ($this->_stage_bootstrap['_stage_output'] === true) return;
        // $this->_stage_output();
    }

}
