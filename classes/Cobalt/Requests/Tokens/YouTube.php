<?php
namespace Cobalt\Requests\Tokens;

use DateTime;

class YouTube extends TokenInterface {

    public function getRefresh(): string { return "";}

    public function setRefresh(): string { return "";}
    
    public function getEditView(): string {
        return "";
    }

    public function getKey(): string {
        return "";
    }

    public function getSecret(): string {
        return "";
    }

    public function getToken(): string {
        return $this->__token['token'] ?? "";
    }

    public function getTokenType(): string {
        return "Parameter";
    }

    public function getTokenPrefix(): string {
        return "key";
    }

    public function getTokenExpiration(): ?DateTime {
        return null;
    }

    public function setKey(): ?string {
        return "";
    }

    public function setSecret(): ?string {
        return "";
    }

    public function setToken(): string {
        return "";
    }

    public function setTokenType(): ?string {
        return $this->getTokenType();
    }

    public function setTokenPrefix(): ?string {
        return $this->getTokenPrefix();
    }

    public function setTokenExpiration(): ?DateTime {
        return null;
    }
    function getEndpoint():array {
        return [];
    }

    function setEndpoint():string {
        return "";
    }
}
