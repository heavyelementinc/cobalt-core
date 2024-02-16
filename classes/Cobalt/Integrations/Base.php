<?php

namespace Cobalt\Integrations;

abstract class Base {

    var $config = [
        'auth_type' => ['']
    ];

    /**
     * This returns a configuration object
     * @param mixed $keys 
     * @return Config 
     */
    abstract static function configuration($keys):Config;

    /**
     * This should return the contents of the button that takes you to the token management screen
     * @return string 
     */
    abstract static function token_index_entry(Config $config):string;

    /**
     * This should return the HTML for the token management screen
     * @param Config $config 
     * @return string 
     */
    abstract static function tokens(Config $config):string;

    function fetch($method, $action, $body = "", $headers = "") {

    }

    

}