<?php

namespace Cobalt\Requests\OAuth;

use Cobalt\Requests\OAuth\OAuth;

/** @package Cobale\Requests\OAuth */
class Google extends OAuth {

    public function getTokenUri(): ?string {
        return $this->tokenData["token_uri"];
    }

    function fetchTokenData() {
        $this->tokenData = json_decode(file_get_contents(__APP_ROOT__ . "/config/oauth.keys.json"), true)['web'];
    }

    public function get_collection_name() { }

    public function getEndpoint(): ?string {
        return $this->tokenData['auth_uri'];
    }

    public function getClientId(): ?string {
        return $this->tokenData['client_id'];
    }

    public function getClientSecret(): ?string {
        return $this->tokenData['client_secret'];
    }

    public function getRedirectUri(): ?string {
        return $this->tokenData['redirect_uris'][$GLOBALS['CONFIG']['oauth_index']];
    }

    public function getScopes(array $scopes): ?string {
        return implode(" ",$scopes);
    }

    public function getGrantType(): ?string {
        return "authorization_code";
    }

    public function getRequestType(): ?string {
        return "code";
    }

    public function getState(): ?string {
        return "";
    }

    public function getAccessType(): ?string {
        return "offline";
    }

    public function getRefreshToken(): ?string {
        return $this->tokenData['refresh_token'];
    }
    
    public function getRefreshGrantType(): ?string {
        return "refresh_token";
    }
}
