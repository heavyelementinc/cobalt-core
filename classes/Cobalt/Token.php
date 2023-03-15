<?php

namespace Cobalt;

use DateTime;
use Exception;
use Iterator;
use MongoDB\BSON\UTCDateTime;
use Stringable;

/**
 * Generates a cryptographically secure hexadecimal string with optional expiration
 * 
 * @param ?string $token - A string that is used as a token. If null, a new token is generated.
 * @param null|string|\DateTime|\MongoDB\BSON\UTCDateTime - If null, no expiration is set. If string, the string is parsed as a date string. If Mongo\UTCDateTime, it's converted to a DateTime
 * @package Cobalt
 */
class Token implements Stringable, Iterator {
    private string $token;
    protected int $byte_length = 16;
    protected int $str_length;
    protected string $type = "token";
    protected ?\DateTime $expires = null;

    function __construct(?string $token = null, null|\DateTime|\MongoDB\BSON\UTCDateTime $expiration = null, $type = "token") {
        $this->str_length = $this->byte_length * 2;
        if($token) $this->set_token($token);
        if(!$token) $this->generate_token();
        if($expiration) $this->set_expiration($expiration);
        $this->set_type($type);
    }

    /**
     * Generates a cryptographically secure string.
     * @return string new hexadecimal string
     * @throws Exception 
     */
    function generate_token():string {
        $this->token = bin2hex(random_bytes($this->byte_length));
        return $this->token;
    }

    function get_token():string {
        return $this->token;
    }

    function set_token(string $token):void {
        if(!ctype_xdigit($token)) throw new Exception("Invalid characters");
        if(!strlen($token) === $this->str_length) throw new Exception("Invalid token length");
        // todo: check byte length;
        $this->token = $token;
    }

    function get_type() {
        return $this->type;
    }

    function set_type($type) {
        $this->type = $type;
    }

    function __toString():string {
        return $this->get_token();
    }

    function set_expiration(string|\DateTime|\MongoDB\BSON\UTCDateTime $exp):\DateTime {
        $date = $exp;
        switch(gettype($exp)) {
            case "string":
                $date = new \DateTime($exp);
            case "object":
                if($exp instanceof \DateTime) break;
                if(method_exists($exp, 'toDateTime')) $date = $exp->toDateTime();
                break;
        }
        $this->expires = $date;
        return $date;
    }

    function get_expires() {
        return $this->expires;
    }

    function is_expired() {
        if(!$this->expires) return false;
        $now = new DateTime();
        $date = $this->expires;
        if($date instanceof UTCDateTime) $date = $date->toDateTime();
        if($now->getTimestamp() < $date->getTimestamp()) return true;
        return false;
    }

    var $index = 0;
    var $map = ['token', 'expires', 'type'];
    public function current(): mixed {
        return $this->{$this->key()};
    }

    public function next(): void {
        $this->index += 1;
    }

    public function key(): mixed {
        return $this->map[$this->index];
    }

    public function valid(): bool {
        return $this->index < count($this->map);
    }

    public function rewind(): void {
        $this->index = 0;
    }
}
