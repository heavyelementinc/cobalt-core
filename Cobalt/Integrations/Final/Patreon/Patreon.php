<?php

namespace Cobalt\Integrations\Final\Patreon;

use Cobalt\Integrations\Base;
use Cobalt\Integrations\Config;
use Cobalt\Integrations\OauthBase;

class Patreon extends Base {

    public function fetchAllMembershipData():array {
        $cli = function_exists("say");
        $params = [

        ];
        $result = [];
        $cursor = null;
        $iterations = 0;
        $total = null;
        while(true) {
            $response = $this->fetchPage($cursor)['response'];
            $total = ceil($response['meta']['pagination']['total'] / count($response['data']));
            if($cli) print("Fetched ".fmt("Patreon", "i")." memberships ($iterations/$total)");
            $result[$cursor] = $response;
            if(!key_exists('cursors', $response['meta']['pagination'])) break;
            if(!key_exists('next', $response['meta']['pagination']['cursors'])) {
                say("Path to cursor does not exist, breaking...", 'e');
                break;
            }
            $cursor = $response['meta']['pagination']['cursors']['next'];
            
            $iterations += 1;
            if($iterations >= $total) break;
            print("\r");
            // break;
        }
        print("\n");
        return $result;
    }

    private function fetchPage(?string $cursor = null) {
        $query = [
            // 'scope' => implode(",", [
            //     "campaigns.members"
            // ]),
            'include' => implode(",", [
                "currently_entitled_tiers",
                "address",
                "user",
            ]),
            'fields[member]' => implode(",", [
                'campaign_lifetime_support_cents',
                'currently_entitled_amount_cents',
                'email',
                'full_name',
                'is_follower',
                'last_charge_date',
                'last_charge_status',
                'lifetime_support_cents',
                'next_charge_date',
                'note',
                'patron_status',
                'pledge_cadence',
                'pledge_relationship_start',
                'will_pay_amount_cents',
            ]),
            'fields[tier]' =>    implode(",", [
                "amount_cents",
                "created_at",
                "description",
                "discord_role_ids",
                "edited_at",
                "patron_count",
                "published",
                "published_at",
                "requires_shipping",
                "title",
                "url",
            ]),
            'fields[user]' => implode(",", [
                'about',
                'can_see_nsfw',
                'created',
                'email',
                'first_name',
                'full_name',
                'hide_pledges',
                'image_url',
                'is_email_verified',
                'last_name',
                'like_count',
                'social_connections',
                'thumb_url',
                'url',
                'vanity',
            ]),
            // 'fields[address]' => implode(",", [
            //     "addressee",
            //     // "city",
            //     // "line_1",
            //     // "line_2",
            //     // "phone_number",
            //     // "postal_code",
            //     // "state",
            // ])
        ];
        $campaign = $this->config->campaign_id;
        if($cursor !== null) $query['page[cursor]'] = $cursor;
        $url = "https://www.patreon.com/api/oauth2/v2/campaigns/$campaign/members?" . http_build_query($query);
        return $this->fetch('get', $url);
    }


    public function publicName(): string {
        return "Patreon";
    }

    public function publicIcon(): string {
        return "patreon";
    }

    public function get_unique_token(): string {
        return "patreon";
    }

    public function configuration(): Config {
        return new PatreonConfig();
    }

    public function status(): int {
        return self::STATUS_CHECK_OK;
    }

    public function html_token_editor(): string {
        return view("Cobalt/Integrations/Final/Patreon/templates/edit.php");
    }
    // public function oauth_errors(): array {
    //     return [];
    // }

    // public function publicName(): string {
    //     return "Patreon";
    // }

    // public function publicIcon(): string {
    //     return "patreon";
    // }

    // public function get_unique_token(): string {
    //     return "";
    // }

    // public function configuration(): Config {
    //     return new PatreonConfig();
    // }

    // public function status(): int {
    //     return 0;
    // }

    // public function html_token_editor(): string {
    //     return view("Cobalt/Integrations/Patreon/templates/edit.php");
    // }

}