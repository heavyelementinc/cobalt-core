<?php
namespace Cobalt\Requests\Tokens;

class GoogleOAuth extends TokenInterface {

    public function getToken(): string {
        return $this->__token['token'];
    }

    public function getSecret(): string {
        return $this->__token['secret'];
    }

    public $map = [
        'key' => [
            'get'=>'getKey',
            'set'=>'setKey',
            'mutable' => true,
        ],
        'secret' => [
            'get'=>'getSecret',
            'set'=>'setSecret',
            'mutable' => true,
        ],
        'token'      => [
            'get'=>'getToken',
            'set'=>'setToken',
            'mutable' => true,
        ],
        'endpoint'   => [
            'get'=>'getEndpoint',
            'set'=>'setEndpoint',
            'mutable' => true,
        ],
        'refresh'      => [
            'get'=>'getRefresh',
            'set'=>'setRefresh',
        ],
        'type'       => [
            'get'=>'getTokenType',
            'set'=>'setTokenType',
            'mutable' => true,
        ],
        'prefix'     => [
            'get'=>'getTokenPrefix',
            'set'=>'setTokenPrefix',
            'mutable' => true,
        ],
        'expiration' => [
            'get'=>'getTokenExpiration',
            'set'=>'setTokenExpiration',
            'mutable' => true,
        ],
        'encoding'   => [
            'get'=>'getEncoding',
            'set'=>'setEncoding',
        ],
        "client_id" => [
            'mutable' => true
        ],
        "project_id" => [
            'mutable' => true
        ],
        "auth_uri" => [
            'mutable' => true
        ],
        "token_uri" => [
            'mutable' => true
        ],
        "auth_provider_x509_cert_url" => [
            'mutable' => true
        ],
        "client_secret" => [
            'mutable' => true
        ],
        "redirect_uris" => [
            'mutable' => true
        ],
    ];
}
