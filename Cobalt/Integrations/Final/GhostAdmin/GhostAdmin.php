<?php

namespace Cobalt\Integrations\Final\GhostAdmin;

use Cobalt\Integrations\Final\Ghost\Ghost;
use Cobalt\Integrations\Final\Ghost\GhostConfig;
use DateTime;

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
            if($cli) print("\r");
        }
        if($cli) print("\n");
        return $result;
    }

    public function getMembershipDataPage($cursor = null) {
        $host = $this->get_host();
        $result = [];
        if($cursor) $page = "&page=$cursor";
        $response = $this->fetch("GET", "$host/ghost/api/admin/members/?include=newsletters%2Clabels&limit=100$page");
        return $response;
    }

    public function yieldMembershipData() {
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
            // array_push($result, $response['response']['members']);
            $cursor = $response['response']['meta']['pagination']['next'];
            foreach($response['response']['members'] as $member) {
                yield $member;
            }
            if($cursor === null) break;
            $iterations += 1;
            if($iterations > $response['response']['meta']['pagination']['pages']) {
                break;
            }
            if($cli) print("\r");
        }
        if($cli) print("\n");
        return $result;
    }

    public function publicName(): string {
        return "Ghost Admin";
    }

    const MEMBER_STATUS__ACTIVE = "active";
    const MEMBER_INTERVAL__YEAR = "year";
    const MEMBER_INTERVAL__MONTH = "month";
    static function isActiveMembership(array $member):array|false {
        $candidate = false;
        foreach($member['subscriptions'] as $sub) {
            if($sub['status'] !== self::MEMBER_STATUS__ACTIVE) continue;
            if(!$candidate) $candidate = $sub;
            $candidateStart = new DateTime($candidate['start_date']);
            $subEnd = new DateTime($sub['start_date']);
            if($candidateStart->diff($subEnd)->s >= 1) {
                $candidate = $sub;
            }
        }
        return $candidate;
    }
}