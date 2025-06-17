<?php

namespace Cobalt\Captcha\Exceptions;

use Exceptions\HTTP\Confirm;
use Exceptions\HTTP\HTTPException;

class HumanTest extends HTTPException {
    public $status_code = 300;
    public $name = "Additional Information Required";

    function __construct($message, $data, $okay = "Continue", $dangerous = true) {
        parent::__construct($message, true, $data);
        header("X-Captcha: $message");
    }
}