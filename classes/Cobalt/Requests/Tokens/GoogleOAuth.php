<?php
namespace Cobalt\Requests\Tokens;

use DateTime;
use MongoDB\BSON\UTCDateTime;

class GoogleOAuth extends TokenInterface {

    public function getRefresh(): string|null { return $this->__token['refresh_token'];}

    public function setRefresh(): string { return "";}
    
    public function getEditView(): string {
        return "";
    }

    public function getKey(): string {
        return "";
    }

    public function getSecret(): string {
        return "";
    }

    public function getToken(): string {
        return $this->__token['access_token'] ?? "";
    }

    public function getMiscParameters(): array {
        return ['scope' => $this->__token['scope']];
    }

    public function getTokenType(): string {
        return "Authorization";
    }

    public function getTokenPrefix(): string {
        return "Bearer";
    }

    public function getTokenExpiration(): ?DateTime {
        if(!$this->__token) return null;
        $date = $this->__token['_id']->getTimestamp();
        if(key_exists('__last_refreshed', $this->__token)) $date = $this->__token['__last_refreshed']->getTimestamp();
        $dt = new DateTime();
        $dt->setTimestamp($date + $this->__token['expires_in']);
        return $dt;
    }

    public function setKey(): ?string {
        return "";
    }

    public function setSecret(): ?string {
        return "";
    }

    public function setToken(): string {
        return "";
    }

    public function setTokenType(): ?string {
        return $this->getTokenType();
    }

    public function setTokenPrefix(): ?string {
        return $this->getTokenPrefix();
    }

    public function setTokenExpiration(): ?DateTime {
        return null;
    }
    function getEndpoint():array {
        $file = json_decode(file_get_contents(__APP_ROOT__ . "/config/oauth.keys.json"));
        return [
            'endpoint' => "https://oauth2.googleapis.com/token",
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'params' => [
                'client_id' => $file['client_id'],
                'client_secret' => $file['client_secret'],
                'refresh_token' => $this->getRefresh(),
                'grant_type' => "refresh_token"
            ]
        ];
    }

    function setEndpoint():string {
        return "";
    }
}
