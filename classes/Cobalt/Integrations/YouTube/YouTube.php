<?php

namespace Cobalt\Integrations\YouTube;

use Auth\UserCRUD;
use Cobalt\Integrations\OauthBase;
use Cobalt\Integrations\Config;
use Cobalt\Integrations\YouTube\Config as YouTubeConfig;
use DateTime;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Unauthorized;
use Cobalt\Integrations\Base;
use Drivers\Database;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class YouTube extends OauthBase {

    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }
    public function status(): int {
        return self::STATUS_CHECK_OK;
    }
    

    public function publicName(): string {
        return "YouTube OAuth";
    }

    public function publicIcon(): string {
        return "youtube";
    }

    function get_unique_token(): string {
        return "YouTubeToken";
    }

    public function configuration(): Config {
        return new YouTubeConfig();
    }

    public function html_token_editor(): string {
        return view("/admin/integrations/edit/youtube-api.html");
    }

    function oauth_errors():array {
        return [
            'access_denied' => [
                'callback' => fn ($err_code) => false,
                'message' => fn ($err_code) => "Access Denied"
            ]
        ];
    }

    function fetchYouTubeMembershipTiers() {
        $params = [
            'part' => 'id,snippet'
        ];
        return $this->fetch("GET", "https://www.googleapis.com/youtube/v3/membershipsLevels?" . http_build_query($params));
    }

    function fetchAllYouTubeMembers() {
        // $youtube_tiers = $this->fetchYouTubeMembershipTiers()['response'];
        $fetched_data = [];
        $pagination = null;
        while(true) {
            $request = $this->fetchYouTubeMembersByPageToken($pagination);
            array_push($fetched_data, $request);
            if(!$request->nextPageToken) break;
            $pagination = $request->nextPageToken;
        }
        $members = [];
        foreach($fetched_data as $request => $payload) {
            foreach($payload->items as $index => $d) {
                $snippet = $d->snippet;
                $members[$snippet->memberDetails->channelId] = $snippet;
            }
        }
        return $members;
    }
    
    /**
     * 
     * @param mixed $page_token 
     * @return stdClass
     * @throws GuzzleException
     * @throws RuntimeException
     */
    public function fetchYouTubeMembersByPageToken($page_token = null) {
        $params = [
            'part' => 'snippet',
            'mode' => 'all_current',
            'maxResults' => 1000
        ];
        if($page_token) $params['pageToken'] = $page_token;
        return json_decode($this->fetch("GET", "https://www.googleapis.com/youtube/v3/members?" . http_build_query($params))['result']);
    }

    public function handleError($error, &$request):int {
        return self::ERROR_HANDLING['UNHANDLED_ERROR'];
    }
}