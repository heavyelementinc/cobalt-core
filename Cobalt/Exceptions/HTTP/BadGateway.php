<?php
namespace Exceptions\HTTP;
class BadGateway extends HTTPException{
    public $status_code = 502;
    public $name = "Bad Gateway";
}