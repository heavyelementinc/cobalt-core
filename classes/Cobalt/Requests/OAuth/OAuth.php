<?php

namespace Cobalt\Requests\OAuth;

use Drivers\Database;

abstract class OAuth extends Database {
    protected $tokenData = null;

    function __construct() {
        parent::__construct(null, "CobaltTokens");
        $this->fetchTokenData();
    }

    function get_collection_name() {
        return "CobaltTokens";
    }

    abstract function getEndpoint(): ?string;

    abstract function getClientId(): ?string;

    abstract function getClientSecret(): ?string;

    abstract function getRedirectUri(): ?string;

    abstract function getTokenUri(): ?string;

    abstract function getGrantType(): ?string;

    abstract function getRequestType(): ?string;

    abstract function getScopes(array $scopes): ?string; // Do we need this?

    abstract function getState(): ?string;

    abstract function getAccessType(): ?string;

    abstract function getRefreshToken(): ?string;

    abstract function getRefreshGrantType(): ?string;

    public function getLink($scopes): string {
        $parameters = [
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => $this->getRequestType(),
            'scope' => $this->getScopes($scopes),
            'access_type' => $this->getAccessType(),
        ];
        return $this->getEndpoint() . "?" . http_build_query($parameters);
    }

    abstract function fetchTokenData();


    private function exchangeCodeForToken($code) {
        $parameters = [
            'code' => $code['code'],
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'redirect_uri' => $this->getRedirectUri(),
            // 'access_type' => $this->getAccessType(),
            'grant_type' => $this->getGrantType(),
        ];
        $uri = $this->getTokenUri();
        $result = fetch($uri . "?" . http_build_query($parameters), "POST", ['Content-Type' => "application/x-www-form-urlencoded"]);
        return $result;
    }

    public function storeIncomingOAuth() {
        // Use $_GET parameters to associate with the 
        $token = $this->exchangeCodeForToken($_GET);
        $token['for'] = session('_id');
        $token['type'] = "oauth";

        $token['platform'] = substr($this::class, strrpos($this::class,"\\") +1) . "OAuth";

        $this->insertOne($token);
        return $token;
    }

    private function getToken() {
        // Return the current token OR get a refreshed token and then return said token
    }

    public function fetchFreshToken($data) {
        $this->tokenData = $data;
        // Returns a fresh token from the API endpoint
        $params = [
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'refresh_token' => $this->getRefreshToken(),
            'grant_type' => $this->getRefreshGrantType(),
        ];

        $uri = $this->getTokenUri();
        $result = fetch($uri . "?" . http_build_query($params), "POST", ['Content-Type' => "application/x-www-form-urlencoded"]);
        $this->updateOne([
            '_id' => $data['_id']
        ],
        [
            '$set' => $result
        ]);
        return $result;
    }
}
