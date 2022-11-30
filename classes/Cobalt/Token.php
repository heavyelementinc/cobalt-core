<?php

namespace Cobalt;

use DateTime;
use Exception;
use Stringable;

/**
 * Generates a cryptographically secure hexadecimal string with optional expiration
 * 
 * @param ?string $token - A string that is used as a token. If null, a new token is generated.
 * @param null|string|\DateTime|\MongoDB\BSON\UTCDateTime - If null, no expiration is set. If string, the string is parsed as a date string. If Mongo\UTCDateTime, it's converted to a DateTime
 * @package Cobalt
 */
class Token implements Stringable {
    private string $token;
    protected int $byte_length = 16;
    protected int $str_length;
    protected ?\DateTime $expires = null;

    function __construct(?string $token = null, null|string|\DateTime|\MongoDB\BSON\UTCDateTime $expiration = null) {
        $this->str_length = $this->byte_length * 2;
        if($token) $this->set_token($token);
        if(!$token) $this->generate_token();
        if($expiration) $this->set_expiration($expiration);
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

    function getExpires() {
        return $this->expires;
    }
}
