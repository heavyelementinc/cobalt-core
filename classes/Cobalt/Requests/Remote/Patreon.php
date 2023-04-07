<?php

namespace Cobalt\Requests\Remote;

class Patreon extends API {
    public $json_parse_as_array = true;

    public function getAllPatrons($cli = true) {
        if($cli) say("Connecting to Patreon API...");
        $result = [];
        $cursor = null;
        $iterations = 1;
        $total = null;
        while(true) {
            $r = $this->getPatronPage(null, $cursor);
            
    
            if($total === null) {
                $total = ceil($r['meta']['pagination']['total'] / count($r['data']));
                if($cli) printf("Total API calls to make: $total... 1", "i");
            }

            if(!key_exists('cursors', $r['meta']['pagination'])) break;
            if(!key_exists('next', $r['meta']['pagination']['cursors'])) {
                say("Path to cursor does not exist, breaking...", 'e');
                break;
            }
            if(!$r['meta']['pagination']['cursors']['next']) {
                say("No cursor, breaking...", 'e');
                break;
            }

            // Set up for next iteration
            $cursor = $r['meta']['pagination']['cursors']['next'];
            if(key_exists($cursor, $result)) {
                say("Found cursor in result... breaking");
                break;
            }
            $result[$cursor] = $r;
            $iterations += 1;

            // Log to the console:
            if($cli) printf(", $iterations");

            if($iterations >= $total) break;
            
        }
        printf("\n");
        say(" done", 'i');
        return $result;
    }

    public function getPatronPage($campaign_id = null, $cursor = null) {
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

        if($cursor !== null) $query['page[cursor]'] = $cursor;

        $campaign = $campaign_id ?? $this->token->key;

        $url = "https://www.patreon.com/api/oauth2/v2/campaigns/$campaign/members?" . http_build_query($query);
        return $this->get($url);
    }

    public function getPatronData($id, ?array $fields = null) {
        $queryString = "";
        if($fields) $queryString = "?" . http_build_query(['fields' => implode(",",$fields)]);
        return $this->get("https://".$this->token->key.".myshopify.com/admin/api/2022-10/orders/$id.json$queryString");
    }



    public function getIfaceName(): string {
        return "\\Cobalt\\Requests\\Tokens\\Patreon";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function refreshTokenCallback($result): string {
        return "";
    }

    public static function getMetadata(): array {
        return [
            'icon' => "<i name='patreon'></i>",
            'name' => "Patreon",
            'view' => "/admin/api/editors/patreon.html",
        ];
    }

    public function testAPI(): bool {
        return true;
    }

    public function sanityCheck($url, $method, $body = null):bool|string {
        return true;    
    }
    // public function errorHandler($error, $message):string {
    //     if($error->code === 404) return "Not found";
    //     return $message;
    // }
}
