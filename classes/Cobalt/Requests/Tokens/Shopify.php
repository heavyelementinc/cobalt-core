<?php
namespace Cobalt\Requests\Tokens;

class Shopify extends TokenInterface {

    public function getRefresh(): string {
        return "";
    }

    public function setRefresh(): string {
        return "";
    }
    public function getEditView(): string {
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
        return "X-Header";
    }
    function getTokenPrefix():string{
        return "X-Shopify-Access-Token";
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
    function getEndpoint():array {
        return [];
    }

    function setEndpoint():string {
        return "";
    }
}
