<?php

namespace Cobalt\Integrations\Final\YouTube;

use Auth\UserCRUD;
use Cobalt\Integrations\Config as IntegrationsConfig;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use DateInterval;
use DateTime;
use Drivers\Database;
use Exception;
use MongoDB\BSON\UTCDateTime;

class YouTubeConfig extends IntegrationsConfig {

    public function __set_manager(?Database $manager = null): ?Database {
        return null;//new YouTube();
    }

    public function getToken(): string {
        // /** @var BSONDocument */
        // $token = session()->integrations->YouTubeToken;
        $url = "https://oauth2.googleapis.com/token";
        $tokenName = $this->tokenName;
        $token = session()->integrations->{$tokenName};
        if(is_null($token)) {
            redirect_and_exit("Location: /me#extensions");
            exit;
        }
        $userTokens = $token->getRaw();
        $asOf = $userTokens['fresh_as_of']->toDateTime();
        $expiry = $asOf->add(DateInterval::createFromDateString($userTokens['details']['expires_in'] . " seconds"));
        $now = new DateTime();
        if($expiry > $now) {
            return $userTokens['details']['access_token'];
        }
        if(!isset($userTokens['details']['refresh_token'])) throw new Exception("This token has expired and it has no refresh token. Is it an offline token?");
        $body = [
            'client_id'  => (string)$this->client_id,	
            'client_secret' => (string)$this->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $userTokens['details']['refresh_token'],
        ];
        $yt = new YouTube();
        $yt->requestEncoding(REQUEST_ENCODE_FORM);
        $result = $yt->fetch("POST", 
            $url, 
            $body, 
            [], // ['Authentication' => 'Bearer '.$this->getToken()], 
            false
        );
        $yt->requestEncoding(null);
        
        $userCRUD = new UserCRUD();
        $userCRUD->update_integration_credentials(session('_id'), $this->tokenName, $result['response'], $now);
        return $result['response']['access_token'];
    }

    public function getParam(): string {
        return "";
    }

    public function fields(): array {
        return [
            "client_id" => new StringResult,
            "project_id" => new StringResult,
            "auth_uri" => new StringResult,
            "token_uri" => new StringResult,
            "auth_provider_x509_cert_url" => new StringResult,
            "client_secret" => new StringResult,
            "redirect_uris" => [
                new ArrayResult,
            ],
            "scope" => new ArrayResult,
            "access_type" => [
                new EnumResult,
                'valid' => [
                    'online' => 'Online',
                    'offline' => 'Offline',
                ],
                'default' => 'offline'
            ]
        ];
    }
    
}