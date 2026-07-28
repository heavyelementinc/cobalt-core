<?php
namespace Exceptions\HTTP;
use Exceptions\HTTP\HTTPException;

class Forbidden extends HTTPException {
    public $status_code = 403;
    public $name = "Forbidden";
}