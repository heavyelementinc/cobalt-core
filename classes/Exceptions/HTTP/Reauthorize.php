<?php

namespace Exceptions\HTTP;

/**
 * Send a confirmation dialog to the client.
 * @package Exceptions\HTTP
 */
class Reauthorize extends HTTPException {
    public $status_code = 303;
    public $name = "See Other";

    function __construct($message, $data, $okay = "Continue", $required_header = ['X-Reauthorization' => "true"]) {
        header("X-Reauthorization-Request: Password");
        parent::__construct($message);
        $this->data = [
            'return' => $data,
            'headers' => $required_header,
            'okay' => $okay,
        ];
    }
}
