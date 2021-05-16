<?php

/**
 * Shared Content Handler
 * 
 * This handler extends WebHandler and overrides certain problematic methods.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Handlers;

class SharedHandler extends WebHandler {
    private $core_content = __ENV_ROOT__ . "/shared/";
    private $filename = "";

    public function _stage_execute($router_result = "") {
        "test";
    }
}
