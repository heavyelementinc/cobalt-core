<?php

namespace Cobalt\Integrations;

use TypeError;

abstract class Config {
    private $keys;

    function __constructor($keys) {
        /**
         * [
         *      'auth_type' => 0,
         *      'client_secret' => '',
         *      'auth_key' => '',
         * ]
         */
        $this->setKeys($keys);
    }

    function setKeys($keys){
        $this->keys = doc_to_array($keys);
    }

    function getAuth() {
        switch($this->keys['auth_type']) {
            case "0":
            case 0:
                return "Bearer";
            case "1":
            case 1:
                return "";
            case "2":
            case 2:
                if(method_exists($this->keys,'auth_callback')) return $this->{'auth_callback'}($this->keys);
            default:
                throw new TypeError("Invalid auth_type");
        };
    }


}