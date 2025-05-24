<?php

namespace Cobalt\Integrations\Ghost;

class GhostAdmin extends Ghost {
    function __construct() {
        parent::__construct();
        $this->config->setMode(GhostConfig::MODE_ADMIN);
    }

    public function getMembers() {
        $host = $this->get_host();
        while(true) {
            $result = $this->fetch("GET", "$host/admin/members/?include=newsletters%2Clabels");
            
        }
    }
}