<?php

namespace Cobalt\Remote;

use GuzzleHttp\Client;

abstract class Hook {

    private Client $client;
    private Authenticator $auth;
    private bool $hasBeenInitialized = false;
    
    public function setAuthenticator(Authenticator $auth) {
        $this->auth = $auth;
    }

    public function fetch(string $relativePath, array $details = [
        "params" => [],
        "headers" => [],
        "body" => ""
    ], string $method = "GET") {
        if(!$this->client) $this->client = new Client();
        $this->auth->getCredentials($details);
        $url = $this->rebuildURL($relativePath, $details['params']);
        $response = $this->client->request($method, $url);
    }

    private function rebuildURL(string $location, array $addtlParams) {
        $url = $this->baseURL() . $location;
        $parsed = parse_url($url);
        $finalResult  = "$parsed[scheme]://";
        if($parsed['user']) {
            $finalResult .= "$parsed[user]";
            if($parsed['pass']) $finalResult .= ":$parsed[pass]";
            $finalResult .= "@";
        }
        $finalResult .= $parsed['host'];
        if($parsed['port']) $finalResult .= ":$parsed[port]";
        $finalResult .= $parsed['path'];
        if(isset($parsed['query'])) {
            $queryParse = [];
            parse_str($parsed['query'], $queryParse);
            $addtlParams = array_merge($queryParse, $addtlParams);
        }

        if(!empty($addtlParams)) $finalResult .= "?". http_build_query($addtlParams);

        if($parsed['fragment']) $finalResult .= "#$parsed[fragment]";
        
        return $finalResult;
    }

    abstract protected function baseURL():string;
    abstract protected function parse(mixed &$response, bool &$handled);
}