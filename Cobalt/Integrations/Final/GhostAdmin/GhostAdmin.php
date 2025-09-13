<?php

namespace Cobalt\Integrations\Final\GhostAdmin;

use Cobalt\Integrations\Final\Ghost\Ghost;
use Cobalt\Integrations\Final\Ghost\GhostConfig;
use Cobalt\Membership\Enums\PaymentCadence;
use Cobalt\Membership\Enums\Platform;
use Cobalt\Membership\Membership;
use DateTime;
use MongoDB\BSON\UTCDateTime;

class GhostAdmin extends Ghost {

    const COMPLEMENTARY_MEMBERSHIP = "Complimentary";

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
        // $cli = function_exists("say");
        $host = $this->get_host();
        $result = [];
        $cursor = null;
        $iterations = 0;
        $total = null;
        while(true) {
            $page = "";
            if($cursor) $page = "&page=$cursor";
            $response = $this->fetch("GET", "$host/ghost/api/admin/members/?include=newsletters%2Clabels&limit=100$page");
            // if($cli) print("Fetched ".fmt("Ghost","i"). " memberships (".($iterations + 1)."/".$response['response']['meta']['pagination']['pages'].")");
            // array_push($result, $yresponse['response']['members']);
            $cursor = $response['response']['meta']['pagination']['next'];
            foreach($response['response']['members'] as $member) {
                yield $member;
            }
            if($cursor === null) break;
            $iterations += 1;
            if($iterations > $response['response']['meta']['pagination']['pages']) {
                break;
            }
            // if($cli) print("\r");
        }
        // if($cli) print("\n");
        return $result;
    }

    public function publicName(): string {
        return "Ghost Admin";
    }

    const MEMBER_STATUS__ACTIVE = "active";
    const MEMBER_INTERVAL__YEAR = "year";
    const MEMBER_INTERVAL__MONTH = "month";
    /**
     * Returns an active membership or false if no membership is active
     * @param array $member
     * @param bool $countCompedMembersAsActive
     * @return array|false
     */
    static function isActiveMembership(array $member, bool $countCompedMembersAsActive = false):array|false {
        $candidate = false;
        foreach($member['subscriptions'] as $sub) {
            if($sub['status'] !== self::MEMBER_STATUS__ACTIVE) continue;
            if($countCompedMembersAsActive === false) {
                if($member['comped'] === true || $sub['price']['nickname'] === self::COMPLEMENTARY_MEMBERSHIP) continue;
            }
            if(!$candidate) $candidate = $sub;
            $candidateStart = (new DateTime($candidate['start_date']))->getTimestamp();
            $subEnd = (new DateTime($sub['start_date']))->getTimestamp();
            if($candidateStart > $subEnd) {
                $candidate = $sub;
            }
        }
        return $candidate;
    }

    static function toMembership(array|false $md):Membership {
        $membership = new Membership();
        if($md === false) {
            $membership->nullish();
            return $membership;
        }
        $membership->bsonUnserialize([
            'platform' => Platform::GHOST->value,
            'cents' => $md['price']['amount'],
            'is_active' => $md['active'] == self::MEMBER_STATUS__ACTIVE,
            'start_date' => new UTCDateTime(strtotime($md['tier']['created_at'])),
            'end_date'   => new UTCDateTime(strtotime($md['current_period_end'])),
            'next_pledge' => null,
            'cadence' => match($md['price']['interval']) {
                "month" => PaymentCadence::MONTHLY->value,
                "year"  => PaymentCadence::ANNUAL->value,
            },
        ]);
        return $membership;
    }
}