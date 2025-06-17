<?php

namespace Cobalt\Integrations\Final\YouTube;

use Auth\UserCRUD;
use Cobalt\Integrations\OauthBase;
use Cobalt\Integrations\Config;
use DateTime;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Unauthorized;
use Cobalt\Integrations\Base;
use Cobalt\Integrations\GoogleOauth\GoogleOauth;
use Drivers\Database;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

class YouTube extends GoogleOauth {

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
        return view("Cobalt/Integrations/Final/YouTube/templates/youtube-api.html");
    }

    function oauth_errors():array {
        return [
            'access_denied' => [
                'callback' => fn ($err_code) => false,
                'message' => fn ($err_code) => "Access Denied"
            ]
        ];
    }

    function fetchAllMembershipTiers() {
        $params = [
            'part' => 'id,snippet'
        ];
        return $this->fetch("GET", "https://www.googleapis.com/youtube/v3/membershipsLevels?" . http_build_query($params));
    }

    const PAGE_LIMIT = 25;

    function fetchAllMembershipData() {
        $cli = function_exists("say");
        // $youtube_tiers = $this->fetchYouTubeMembershipTiers()['response'];
        $result = [];
        $cursor = null;
        $iterations = 0;
        while(true) {
            $request = $this->fetchYouTubeMembersByPageToken($cursor);
            if($cli) {
                $totalPages = ceil($request['pageInfo']['totalResults'] / self::PAGE_LIMIT);
                print("Fetched YouTube members (".($iterations + 1)."/$totalPages)");
            }
            array_push($result, $request);
            if(!$request->nextPageToken) break;
            print("\r");
            $cursor = $request->nextPageToken;
        }
        print("\n");
        // $members = [];
        // foreach($fetched_data as $request => $payload) {
        //     foreach($payload->items as $index => $d) {
        //         $snippet = $d->snippet;
        //         $members[$snippet->memberDetails->channelId] = $snippet;
        //     }
        // }
        return $result;
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
            'maxResults' => 15
        ];
        if($page_token) $params['pageToken'] = $page_token;
        return json_decode($this->fetch("GET", "https://www.googleapis.com/youtube/v3/members?" . http_build_query($params))['result'], true);
    }

    private $requestCount = 0;
    public function handleError($error, &$request):int {
        // switch($error->code) {
        //     case 401:
        //         if($this->requestCount < 1) {
        //             $this->requestCount += 1;
        //             $this->refreshToken();
        //         }
        // }
        return self::ERROR_HANDLING['UNHANDLED_ERROR'];
    }

}