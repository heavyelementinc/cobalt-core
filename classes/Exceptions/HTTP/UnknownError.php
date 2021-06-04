<?php

namespace Exceptions\HTTP;

class UnknownError extends HTTPException {
    public $status_code = 500;
    public $name = "Unknown Error";
}
