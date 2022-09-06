<?php

namespace Cobalt\Requests\Tokens;

use Iterator;

abstract class TokenInterface implements Iterator {

    public array|null $__token = [];
    
    function __construct($token_data) {
        $this->__token = $token_data;
    }

    /** Prepare for usage */
    abstract function getKey():string;
    abstract function getSecret():string;
    abstract function getToken():string;
    abstract function getRefresh():string;
    abstract function getTokenType():string;
    abstract function getTokenPrefix():string;
    abstract function getTokenExpiration():\DateTime|null;
    
    /** Prepare for storage */
    abstract function setKey():?string;
    abstract function setSecret():?string;
    abstract function setToken():string;
    abstract function setRefresh():string;
    abstract function setTokenType():?string;
    abstract function setTokenPrefix():?string;
    abstract function setTokenExpiration():\DateTime|null;

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
    ];

    public function __get($name) {
        if(!key_exists($name, $this->map)) return null;
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