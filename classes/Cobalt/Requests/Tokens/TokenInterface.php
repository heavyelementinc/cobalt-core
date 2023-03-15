<?php

namespace Cobalt\Requests\Tokens;

use DateTime;
use Iterator;

abstract class TokenInterface implements Iterator {

    public ?array $__token = [];
    
    function __construct($token_data) {
        if(gettype($token_data) !== "array") $token_data = doc_to_array($token_data);
        $this->__token = $token_data;
    }

    /** Prepare for usage */
    abstract function getToken():string;
    abstract function getSecret():string;
    function getKey():string {
        return $this->__token['key'];
    }
    function getRefresh():string|null {
        return $this->__token['refresh_token'];
    }
    function getTokenType():string {
        return "Authorization";
    }
    function getTokenPrefix():string {
        return "Bearer";
    }
    function getTokenExpiration():\DateTime|null {
        return null;
    }

    /**
     * 
     * @return array with keys 'endpoint', 'method', 'headers', and 'params'
     */
    function getEndpoint():array {
        return [
            'endpoint' => "",
            'method' => "POST",
            'headers' => [],
            'params' => [

            ]
        ];
    }

    function getEncoding():string {
        return "application/json";
    }

    function isTokenStale():bool {
        $date = $this->getTokenExpiration();
        if(!$date) return false;
        $now = (new DateTime())->getTimestamp();
        $difference = ($now >= $date->getTimestamp());
        return $difference;
    }
    
    /** Prepare for storage */
    // abstract function setSecret():?string;
    // abstract function setToken():string;
    // function setKey():?string {
    //     return "";
    // }
    // function setRefresh():string {
    //     return "";
    // }
    // function setTokenType():?string {
    //     return "";
    // }
    // function setTokenPrefix():?string {
    //     return "";
    // }
    // function setTokenExpiration():\DateTime|null {
    //     return null;
    // }
    // function setEndpoint():string {
    //     return "";
    // }
    // function setEncoding():string {
    //     return "";
    // }

    function getMiscParameters():array {
        return [];
    }

    function getMiscHeaders():array {
        return [];
    }

    private $index = 0;
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
    ];

    public function getValidSubmitData() {
        $keys = [];
        foreach($this->map as $key => $meta) {
            if(key_exists('mutable', $meta)) array_push($keys, $key);
        }
        return $keys;
    }

    public function __get($name) {
        $allowMisc = false;
        if(method_exists($this, 'setMisc')) $allowMisc = true;
        if(!key_exists($name, $this->map)) {
            if(!$allowMisc) return null;
            if(key_exists($name, $this->__token)) return $this->__token[$name];
        }
        return $this->{$this->map[$name]['get']}();
    }

    // public function normalize($results) {
    //     $normalized = [];
    //     foreach($results as $name => $value) {
            
    //     }
    //     return $normalized;
    // }

    public function current(): mixed {
        $keys = array_keys($this->map);
        return $this->{$this->map[$keys[$this->index]]['get']}();
    }

    public function key(): mixed {
        return array_keys($this->map)[$this->index];
    }

    public function next(): void {
        $this->index += 1;
    }

    public function rewind(): void {
        $this->index = 0;
    }

    public function valid(): bool {
        if($this->index >= count($this->map)) return false;
        return true;
    }
}
