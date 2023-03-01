<?php

namespace Cobalt\Requests\Tokens;

use DateTime;
use Iterator;

abstract class TokenInterface implements Iterator {

    public array|null $__token = [];
    
    function __construct($token_data) {
        $this->__token = $token_data;
    }

    /** Prepare for usage */
    abstract function getToken():string;
    abstract function getSecret():string;
    function getKey():string {
        return "";
    }
    function getRefresh():string|null {
        return "";
    }
    function getTokenType():string {
        return "";
    }
    function getTokenPrefix():string {
        return "";
    }
    function getTokenExpiration():\DateTime|null {
        return null;
    }

    /**
     * 
     * @return array with keys 'endpoint', 'method', and 'params'
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
    abstract function setSecret():?string;
    abstract function setToken():string;
    function setKey():?string {
        return "";
    }
    function setRefresh():string {
        return "";
    }
    function setTokenType():?string {
        return "";
    }
    function setTokenPrefix():?string {
        return "";
    }
    function setTokenExpiration():\DateTime|null {
        return null;
    }
    function setEndpoint():string {
        return "";
    }
    function setEncoding():string {
        return "";
    }

    function getMiscParameters():array {
        return [];
    }

    private $index = 0;
    private $map = [
        'key' => [
            'get'=>'getKey',
            'set'=>'setKey',
        ],
        'secret' => [
            'get'=>'getSecret',
            'set'=>'setSecret',
        ],
        'token'      => [
            'get'=>'getToken',
            'set'=>'setToken',
        ],
        'endpoint'   => [
            'get'=>'getEndpoint',
            'set'=>'setEndpoint',
        ],
        'refresh'      => [
            'get'=>'getRefresh',
            'set'=>'setRefresh',
        ],
        'type'       => [
            'get'=>'getTokenType',
            'set'=>'setTokenType',
        ],
        'prefix'     => [
            'get'=>'getTokenPrefix',
            'set'=>'setTokenPrefix',
        ],
        'expiration' => [
            'get'=>'getTokenExpiration',
            'set'=>'setTokenExpiration',
        ],
        'encoding'   => [
            'get'=>'getEncoding',
            'set'=>'setEncoding',
        ],
    ];

    public function __get($name) {
        $allowMisc = false;
        if(method_exists($this, 'setMisc')) $allowMisc = true;
        if(!key_exists($name, $this->map)) {
            if(!$allowMisc) return null;
            if(key_exists($name, $this->__token)) return $this->__token[$name];
        }
        return $this->{$this->map[$name]['get']}();
    }

    public function normalize() {
        $normalized = [];
        foreach($this->map as $key => $callbacks) {
            $normalized[$key] = $this->{$callbacks['set']}();
        }
        return $normalized;
    }

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
