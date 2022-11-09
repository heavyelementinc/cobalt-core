<?php
namespace Exceptions\HTTP;
class TooManyRequests extends HTTPException{
    public $status_code = 429;
    public $name = "Too Many Requests";

    function __construct($message, $data = []){
        parent::__construct($message,$data);
    }
}
