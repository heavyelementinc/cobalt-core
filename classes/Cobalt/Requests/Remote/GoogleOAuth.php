<?php

namespace Cobalt\Requests\Remote;

use Cobalt\Requests\OAuth\Google;
use DateTime;
use Exception;
use Exceptions\HTTP\HTTPException;

class GoogleOAuth extends \Cobalt\Requests\Remote\OAuth {

    public function getIfaceName(): string {
        return "\\Cobalt\\Requests\\Tokens\\GoogleOAuth";
    }

    public function getPaginationToken(): array {
        return [];
    }

    // public function refreshTokenCallback($result): mixed {
    //     $now = new DateTime();
    //     $results = new DateTime(strtotime($result->_id->getTimestamp() * .001));
    //     if(($now->diff($results)->s - 300) < $result->expires_in) {
    //         // Return the current refresh token
    //         return $result;
    //     }
    //     $oauth = new Google();
        
    //     $oauth->fetchFreshToken($result);
    // }

    public function testAPI(): bool {
        return true;
    }

    public static function getMetadata(): array {
        return [
            'icon' => "<i name='google'></i>",
            'name' => "Google OAuth",
            'view' => "/admin/api/editors/oauth-google.html"
        ];
    }
    
    public function fetchYouTubeTiers() {
        $params = [
            'part' => 'id,snippet'
        ];
        return $this->get("https://www.googleapis.com/youtube/v3/membershipsLevels?" . http_build_query($params));
    }

    public function fetchAllYouTubeMembers() {
        $tiers = $this->fetchYouTubeTiers();
        $data = [
            
        ];
        $pageination = null;
        while(true) {
            $request = $this->fetchYouTubeMembersByPageToken($pageination);
            
            array_push($data, $request);
            if(!$request->nextPageToken) break;
            $pageination = $request->nextPageToken;
        }
        $members = [];
        foreach($data as $request => $payload) {
            foreach($payload->items as $index => $d) {
                $snippet = $d->snippet;
                $members[$snippet->memberDetails->channelId] = $snippet;
            }
        }
        
        return $members;
    }


    public function fetchYouTubeMembersByPageToken($page_token = null) {
        $params = [
            'part' => 'snippet',
            'mode' => 'all_current',
            'maxResults' => 1000
        ];
        if($page_token) $params['pageToken'] = $page_token;
        return $this->get("https://www.googleapis.com/youtube/v3/members?" . http_build_query($params));
    }


    function getEndpoint():string {
        return "https://oauth2.googleapis.com/token";
    }

}
