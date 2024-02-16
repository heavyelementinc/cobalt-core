<?php

namespace Cobalt\Remote\Services\Patreon;

use Cobalt\Remote\Hook;

class Service extends Hook {
    function __construct() {
        $this->setAuthenticator(new Auth());
    }

    protected function baseURL():string {
        return "https://patreon.com/";
    }
    
    protected function parse(mixed &$response, bool &$handled) {
    }
}