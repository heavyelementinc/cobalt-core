<?php

namespace Cobalt\Integrations\Final\Patreon;

use Cobalt\Integrations\Base;
use Cobalt\Integrations\Config;
use Cobalt\Integrations\OauthBase;
use Cobalt\Membership\Enums\PaymentCadence;
use Cobalt\Membership\Enums\Platform;
use Cobalt\Membership\Membership;
use MongoDB\BSON\UTCDateTime;
use SensitiveParameter;

class Patreon extends Base {

    public bool $honorRateLimit = true;
    private bool $rateLimitOnNextCall = false;

    const STATUS__ACTIVE_PATRON = "active_patron";
    const STATUS__FORMER_PATRON = "former_patron";

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

    const MEMBER_SCOPE__MEMBER  = 0b0001;
    const MEMBER_SCOPE__TIER    = 0b0010;
    const MEMBER_SCOPE__USER    = 0b0100;
    const MEMBER_SCOPE__ADDRESS = 0b1000;
    private function fetchPage(?string $cursor = null, int $scopes = self::MEMBER_SCOPE__MEMBER +
    self::MEMBER_SCOPE__TIER +
    self::MEMBER_SCOPE__USER) {
        if($this->honorRateLimit && $this->rateLimitOnNextCall) {
            sleep($this->config?->sleep_interval?->getValue() ?? .1); // Let's not hit the Patreon API throttling limit.
        }
        $this->rateLimitOnNextCall = true;
        $query = ['include' => implode(",", ["currently_entitled_tiers","address","user",])];
        if($scopes & self::MEMBER_SCOPE__MEMBER == self::MEMBER_SCOPE__MEMBER) {
            $query['fields[member]'] = implode(",", [
                'campaign_lifetime_support_cents','currently_entitled_amount_cents',
                'email','full_name','is_follower','last_charge_date','last_charge_status',
                'lifetime_support_cents','next_charge_date','note','patron_status',
                'pledge_cadence','pledge_relationship_start','will_pay_amount_cents',
            ]);
        }
        if($scopes & self::MEMBER_SCOPE__TIER == self::MEMBER_SCOPE__TIER) {
            $query['fields[tier]'] = implode(",", [
                "amount_cents","created_at","description","discord_role_ids",
                "edited_at","patron_count","published","published_at",
                "requires_shipping","title","url",
            ]);
        }
        if($scopes & self::MEMBER_SCOPE__USER == self::MEMBER_SCOPE__USER) {
            $query['fields[user]'] = implode(",", [
                'about','can_see_nsfw','created','email','first_name','full_name',
                'hide_pledges','image_url','is_email_verified','last_name','like_count',
                'social_connections','thumb_url','url','vanity',
            ]);
        }
        // if($scopes & self::MEMBER_SCOPE__ADDRESS == self::MEMBER_SCOPE__ADDRESS) {
        //     $query['fields[address]'] = implode(",", [
        //         "addressee", "city","line_1","line_2","phone_number","postal_code",
        //         "state",
        //     ]);
        // }
        $campaign = $this->config->campaign_id;
        if($cursor !== null) $query['page[cursor]'] = $cursor;
        $query['page[count]'] = $this->config?->member_cursor_limit?->getValue() ?? 1000;
        $url = "https://www.patreon.com/api/oauth2/v2/campaigns/$campaign/members?" . http_build_query($query);
        $response = $this->fetch('get', $url);
        return $response;
    }

    public array $campaign = [];
    public array $tiers = [];
    public array $benefits = [];

    const CAMPAIGN_SCOPE__CAMPAIGN = 0b0001;
    const CAMPAIGN_SCOPE__TIERS    = 0b0010;
    const CAMPAIGN_SCOPE__BENEFITS = 0b0100;
    const CAMPAIGN_SCOPE__GOALS    = 0b1000;
    public function campaignData(int $scopes = 
    self::CAMPAIGN_SCOPE__CAMPAIGN +
    self::CAMPAIGN_SCOPE__TIERS +
    self::CAMPAIGN_SCOPE__BENEFITS +
    self::CAMPAIGN_SCOPE__GOALS
    ) {
        $cli = function_exists("say");
        if($cli) print("Requesting Patreon campaign details (campaign_id: ".fmt($this->config->campaign_id,"i").")...");
        
        $query = [
            'include' => ['creator'],
            // 'fields'  => []
        ];
        if($scopes & self::CAMPAIGN_SCOPE__CAMPAIGN == self::CAMPAIGN_SCOPE__CAMPAIGN) {
            $query['fields[campaign]'] = implode(",", [
            'created_at','creation_name','discord_server_id','image_small_url',
            'image_url','is_charged_immediately','is_monthly','is_nsfw',
            'main_video_embed','main_video_url','one_liner','patron_count',
            'pay_per_name','pledge_url','published_at','summary','thanks_embed',
            'thanks_msg','thanks_video_url','has_rss','has_sent_rss_notify',
            'rss_feed_title','rss_artwork_url','patron_count','discord_server_id',
            'google_analytics_id'
            ]);
        }
        if($scopes & self::CAMPAIGN_SCOPE__TIERS == self::CAMPAIGN_SCOPE__TIERS) {
            $query['include'][] = 'tiers';
            $query['fields[tier]'] = implode(",",[
                // "amount_cents","created_at","description","discord_role_ids",
                // "edited_at","patron_count","published","published_at",
                // "requires_shipping","title","url",
                'amount_cents','created_at','description','discord_role_ids',
                'edited_at','image_url','patron_count','post_count','published',
                'published_at','remaining','requires_shipping','title','unpublished_at',
                'url','user_limit'
            ]);
        }
        if($scopes & self::CAMPAIGN_SCOPE__BENEFITS == self::CAMPAIGN_SCOPE__BENEFITS){
            $query['include'][] = 'benefits';
            // $query['fields[benefits]'] = implode(',',[
            // 'app_external_id',
            // 'app_meta',
            // 'benefit_type',
            // 'created_at',
            // 'deliverables_due_today_count',
            // 'delivered_deliverables_count',
            // 'description',
            // 'is_deleted',
            // 'is_ended',
            // 'is_published', 
            // 'next_deliverable_due_date',
            // 'not_delivered_deliverables_count',
            // 'rule_type',
            // 'tiers_count',
            // 'title'
            // ]);
        }
        if($scopes & self::CAMPAIGN_SCOPE__GOALS == self::CAMPAIGN_SCOPE__GOALS) {
            $query['include'][] = 'goals';
            // $query['fields[goal]'] = implode(',', [
            // 'amount_cents','completed_percentage','created_at','description',
            // 'reached_at','title'
            // ]);
        }
        if(!empty($query['include'])) $query['include'] = implode(",",$query['include']);
        $response = $this->fetch('get', "https://www.patreon.com/api/oauth2/v2/campaigns/".$this->config->campaign_id."?".http_build_query($query));
        $this->campaign = $response['response']['data']['attributes'];
        foreach($response['response']['included'] as $item) {
            switch($item['type']) {
                case 'tier':
                    $this->tiers[$item['id']] = $item['attributes'];
                    break;
                case 'benefit':
                    $this->benefits[$item['id']] = $item['attributes'];
            }
        }

        if($cli) print(" done.\n");
    }

    public function yieldMembershipData(int $scopes = self::MEMBER_SCOPE__MEMBER + self::MEMBER_SCOPE__TIER + self::MEMBER_SCOPE__USER) {
        $this->campaignData();
        // $cli = function_exists("say");
        $params = [

        ];
        $result = [];
        $cursor = null;
        $iterations = 0;
        $total = null;
        if(function_exists("say")) print("Fetching membership details...\r");
        while(true) {
            $response = $this->fetchPage($cursor, $scopes)['response'];
            $total = ceil($response['meta']['pagination']['total'] / count($response['data']));
            // if($cli) print("Fetched ".fmt("Patreon", "i")." memberships ($iterations/$total)");
            $merged = $this->combinePatronDetails($response);
            foreach($merged as $m){
                if(!empty($m['relationships']['currently_entitled_tiers']['data'])) {
                    foreach($m['relationships']['currently_entitled_tiers']['data'] as $i => $tier) {
                        $m['relationships']['currently_entitled_tiers']['data'][$i]['attributes'] = $this->tiers[$tier['id']];
                    }
                }
                yield $m;
            }
            if(!key_exists('cursors', $response['meta']['pagination'])) break;
            if(!key_exists('next', $response['meta']['pagination']['cursors'])) {
                // if($cli) say("Path to cursor does not exist, breaking...", 'e');
                break;
            }
            $cursor = $response['meta']['pagination']['cursors']['next'];
            
            $iterations += 1;
            if($iterations >= $total) break;
            // if($cli) print("\r");
            // break;
        }
        // if($cli) print("\n");
        return $result;
    }

    private function combinePatronDetails($response) {
        $d = [];
        foreach($response['data'] as $m) {
            $d[$m['relationships']['user']['data']['id']] = $m;
        }
        foreach($response['included'] as $u) {
            $d[$u['id']]['user'] = $u;
        }
        return $d;
    }

    static function isActiveMembership($patron):array|false {
        if(empty($patron['relationships']['currently_entitled_tiers']['data'])) return false;
        $tier = [];
        foreach($patron['relationships']['currently_entitled_tiers']['data'] as $tier) {
            if(empty($tier)) return false;
            if($tier['attributes']['amount_cents'] === 0) return false;
            if($tier) return $patron['attributes'];
        }
        return $tier;
    }

    static function toMembership(array|bool $md):Membership {
        $membership = new Membership();
        if($md === false) {
            $membership->nullish();
            return $membership;
        }
        $membership->bsonUnserialize([
            'platform'   => Platform::PATREON->value,
            'cents'      => $md['currently_entitled_amount_cents'],
            'is_active'  => match($md['patron_status']) {
                self::STATUS__ACTIVE_PATRON => true,
                self::STATUS__FORMER_PATRON => false,
                default => false
            },
            'start_date' => new UTCDateTime(strtotime($md['pledge_relationship_start'])),
            'end_date' => new UTCDateTime(strtotime($md['next_charge_date'])),
            'next_pledge' => new UTCDateTime(strtotime($md['next_charge_date'])),
            'cadence' => match($md['pledge_cadence']) {
                1 => PaymentCadence::MONTHLY->value,
                12 => PaymentCadence::ANNUAL->value,
                default => PaymentCadence::UNKNOWN->value,
            }
            
        ]);
        return $membership;
    }

    static function validateWebhookRequest(#[SensitiveParameter] string $secret, ?string $hash = null, ?string $body = null):bool {
        // $body = json_encode(json_decode($body ?? $_REQUEST['input']));
        // For some reason, this hashing fails unless we use the explicit file_get_contents('php://input')
        $body = $body ?? file_get_contents('php://input') ?? $_REQUEST['input'];
        $foreignHash = $hash ?? $_SERVER['HTTP_X_PATREON_SIGNATURE'];
        $localHash = hash_hmac('md5', $body, $secret);
        $hequals = hash_equals($foreignHash, $localHash);
        return $hequals;
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