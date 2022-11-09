<?php
namespace Cobalt\Requests\Tokens;

class Mailchimp extends TokenInterface {

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
        return base64_encode("mailchimp:" . $this->__token['token']);
        // return $this->__token['token'] ?? "";
    }
    
    function getTokenType():string{
        return "Authorization";
    }

    function getTokenPrefix():string{
        return "Basic";
    }

    function getEncoding():string {
        return "application/json";
    }

    function getEndpoint():string {
        return $this->__token['endpoint'];
    }
    
    function setSecret():string {
        return "";
    }
    function setToken():string {
        return "";
    }

    function setEndpoint():string {
        return "";
    }

}