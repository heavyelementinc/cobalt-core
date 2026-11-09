<?php

namespace Cobalt\Integrations\Final\CloudFlare;

use Cobalt\Integrations\Config;
use Cobalt\Integrations\Base;
use Cobalt\Integrations\IntegrationRemoteException;
use Exceptions\HTTP\BadRequest;
use GuzzleHttp\Exception\ClientException;
use TypeError;

class CloudFlare extends Base {
    public function oauth_errors(): array {
        return [
            'user_denied' => [
                'callback' => fn () => false,
                'message' => fn () => "You denied the request"
            ]
        ];
    }

    public function publicName(): string {
        return "CloudFlare";
    }

    public function publicIcon(): string {
        return "cloudflare";
    }

    public function get_unique_token(): string {
        return "cloudflare";
    }

    public function configuration(): Config {
        return new CloudFlareConfig();
    }

    public function html_token_editor(): string {
        return view("Cobalt/Integrations/Final/CloudFlare/templates/cloudflare.html");
    }

    private function get_host() {
        return "https://api.cloudflare.com/client/v4/zones/{$this->config->zoneId}/";
    }

    public function status():int {
        $result = $this->fetch("GET", 
            "https://api.cloudflare.com/client/v4/user/tokens/verify",
            [],
            []
        );
        if($result['response']['success'] === true) return self::STATUS_CHECK_OK;
        return self::STATUS_CHECK_FAIL;
    }

    public function zone_cache_purge(string $zoneId, ?array $files = null, ?array $tags = null, ?string $prefixes = null): int {
        $data = [];
        if($files !== null) $data['files'] = $files;
        if($tags !== null) $data['tags'] = $tags;
        if($prefixes !== null) $data['prefixes'] = $prefixes;
        if(empty($data)) throw new TypeError("At least one of files, tags or prefixes must be provided");
        /**
         * @var array{response:{errors:array,messages:array,result:{id:string},success:bool}} $result
         */
        $result = $this->fetch("POST",
            "https://api.cloudflare.com/client/v4/zones/".$zoneId."/purge_cache",
            $data
        );
        if($result['response']['success'] === true) return self::STATUS_CHECK_OK;
        return self::STATUS_CHECK_FAIL;
    }
}