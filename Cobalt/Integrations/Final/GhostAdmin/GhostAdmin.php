<?php

namespace Cobalt\Integrations\Final\GhostAdmin;

use Cobalt\Integrations\Final\Ghost\Ghost;
use Cobalt\Integrations\Final\Ghost\GhostConfig;

class GhostAdmin extends Ghost {
    function __construct() {
        parent::__construct();
        $this->config->setMode(GhostConfig::MODE_ADMIN);
    }

    public function fetchAllMembershipData() {
        $cli = function_exists("say");
        $host = $this->get_host();
        $result = [];
        $cursor = null;
        $iterations = 0;
        $total = null;
        while(true) {
            $page = "";
            if($cursor) $page = "&page=$cursor";
            $response = $this->fetch("GET", "$host/ghost/api/admin/members/?include=newsletters%2Clabels&limit=100$page");
            if($cli) print("Fetched ".fmt("Ghost","i"). " memberships (".($iterations + 1)."/".$response['response']['meta']['pagination']['pages'].")");
            array_push($result, $response['response']['members']);
            $cursor = $response['response']['meta']['pagination']['next'];
            if($cursor === null) break;
            $iterations += 1;
            if($iterations > $response['response']['meta']['pagination']['pages']) {
                break;
            }
            print("\r");
        }
        print("\n");
        return $result;
    }

    public function publicName(): string {
        return "Ghost Admin";
    }
}