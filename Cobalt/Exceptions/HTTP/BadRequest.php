<?php
namespace Exceptions\HTTP;
class BadRequest extends HTTPException{
    public $status_code = 400;
    public $name = "Bad Request";
}