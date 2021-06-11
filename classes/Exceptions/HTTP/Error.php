<?php

namespace Exceptions\HTTP;

class Error extends HTTPException {
    public $status_code = 500;
    public $name = "Internal Error";
}
