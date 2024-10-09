<?php

namespace Cobalt\Integrations;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;
use GuzzleHttp\Client;
use TypeError;

abstract class Config extends PersistanceMap {
    // public $keys;
    public string $name;
    public string $publicName;
    public string $tokenName;
    public string $icon;
    const AUTH_BASIC = 1;
    const AUTH_BEARER = 2;
    const AUTH_KEY_HEADER = 4;
    const AUTH_KEY_BODY = 8;
    const AUTH_DIGEST = 16;
    const AUTH_JWT = 32;

    abstract function fields(): array;

    public function __get_schema(): array {
        $fields = $this->fields();
        
        return array_merge($fields, [
            '__token_name' => new StringResult,
            '__auth_mode' => [
                new EnumResult,
                'valid' => [
                    self::AUTH_BASIC => "Basic Authentication",
                    self::AUTH_BEARER => "Token Bearer",
                    self::AUTH_KEY_BODY => "Key Parameter",
                    // self::AUTH_JWT => "JSON Web Token",
                    // self::AUTH_DIGEST => "Digest",
                ],
                'default' => self::AUTH_BEARER
            ],
            '__requestEncoding' => [
                new EnumResult,
                'valid' => [
                    REQUEST_ENCODE_JSON => "application/json",
                    REQUEST_ENCODE_FORM => "x-www-form-urlencoded",
                    REQUEST_ENCODE_XML => "application/xml",
                    // REQUEST_ENCODE_MULTIPART_FORM => "multipart/form-data",
                    // REQUEST_ENCODE_OCTET => "application/octet-stream",
                    REQUEST_ENCODE_PLAINTEXT => "text/plain",
                ],
                'default' => REQUEST_ENCODE_FORM
            ]
        ]);
    }

    /**
     * This function adds authentication to a given request.
     * 
     * @param array $request 
     * @param Client $client 
     * @return void 
     * @throws TypeError 
     */
    function authenticate(array &$request, Client $client):void {
        switch($this->__auth_mode->getValue()) {
            case self::AUTH_BASIC:
                // throw new TypeError("AUTH_BASIC is currently not supported");
                $request['headers']['Authorization'] = "Basic ". base64_encode($this->getParam().":".$this->getToken());
                break;
            case self::AUTH_BEARER:
                $request['headers']['Authorization'] = "Bearer ". $this->getToken();
                break;
            case self::AUTH_KEY_BODY:
                $request['body'][$this->getParam()] = $this->getToken();
                break;
            default:
                throw new TypeError("Invalid auth_type");
        };
    }

    /**
     * This function should return the token for `Authorization: Bearer` or the Password for Basic Authentication
     * @return string
     */
    abstract function getToken():string;

    /**
     * This function should return the body key the API expects OR the username for Basic Authentication 
     * @return string
     */
    abstract function getParam():string;

}