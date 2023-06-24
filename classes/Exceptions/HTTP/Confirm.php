<?php

namespace Exceptions\HTTP;

/**
 * Send a confirmation dialog to the client.
 * @package Exceptions\HTTP
 */
class Confirm extends HTTPException {
    public $status_code = 300;
    public $name = "Multiple Choices";

    function __construct($message, $data, $okay = "Continue", $dangerous = true, $required_header = ['X-Confirm-Dangerous' => "true"]) {
        header("X-Confirm: Multiple choices");
        parent::__construct($message);
        $this->data = [
            'return' => $data,
            'headers' => $required_header,
            'okay' => $okay,
            'dangerous' => $dangerous
        ];
    }
}
