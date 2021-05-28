<?php

namespace CRUD\Exceptions;

class ValidationFailed extends \Exceptions\HTTP\BadRequest {
    public $status_code = 422;
    function __construct($message, $data = []) {
        parent::__construct($message);
        $this->data = $data;
    }
}
