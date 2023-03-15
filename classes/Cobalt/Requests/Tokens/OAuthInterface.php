<?php

namespace Cobalt\Requests\Tokens;

use MongoDB\BSON\UTCDateTime;

abstract class OAuthInterface extends TokenInterface {
    abstract function normalize($value): ?array;
    abstract function getExpirationDate($date = null): UTCDateTime;
    /**
     * 
     * @return array with keys 'endpoint', 'method', 'headers', and 'params'
     */
    function getEndpoint():array {
        return [
            'endpoint' => "",
            'method' => "POST",
            'headers' => [

            ],
            'params' => [

            ]
        ];
    }
}
