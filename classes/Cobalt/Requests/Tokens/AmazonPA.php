<?php
namespace Cobalt\Requests\Tokens;

class AmazonPA extends TokenInterface {

    public function getRefresh(): string {
        return "";
    }

    public function setRefresh(): string {
        return "";
    }
    
    function getKey():string{
        return $this->__token['key'] ?? "";
    }
    function getSecret():string{
        return $this->__token['secret'] ?? "";
    }
    function getToken():string{
        return $this->__token['token'] ?? "";
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