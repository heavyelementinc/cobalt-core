<?php
namespace Exceptions\HTTP;
class ServiceUnavailable extends HTTPException{
    public $status_code = 503;
    public $name = "Service Unavailable";
}