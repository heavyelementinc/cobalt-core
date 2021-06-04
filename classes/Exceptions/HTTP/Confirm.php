<?php

namespace Exceptions\HTTP;

class Confirm extends HTTPException {
    public $status_code = 300;
    public $name = "Multiple Choices";
    function __construct($message, $data, $required_header = ['X-Confirm-Dangerous' => true]) {
        parent::__construct($message);
        $this->data = [
            'return' => [
                ...$data
            ],
            'header' => $required_header
        ];
    }
}
