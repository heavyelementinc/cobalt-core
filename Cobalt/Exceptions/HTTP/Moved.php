<?php

namespace Exceptions\HTTP;

class Moved extends HTTPException {
    public $status_code = 301;
    public $name = "Moved Permanently";
}
