<?php
namespace Cobalt\Requests\Tokens;

class Twitter extends TokenInterface {

    public function getRefresh(): string {
        return "";
    }

    public function setRefresh(): string {
        return "";
    }

    function getKey():string{
        return "";
    }
    function getSecret():string{
        return "";
    }
    function getToken():string{
        return "";
    }
    function getTokenType():string{
        return "Authorization";
    }
    function getTokenPrefix():string{
        return "Bearer";
    }
    function getTokenExpiration():\DateTime|null{
        return null;
    }
    
    /** Prepare for storage */
    function setKey():string {
        return "";
    }
    function setSecret():string {
        return "";
    }
    function setToken():string {
        return "";
    }
    function setTokenType():string {
        return "";
    }
    function setTokenPrefix():string {
        return "";
    }
    function setTokenExpiration():\DateTime|null {
        return null;
    }
}