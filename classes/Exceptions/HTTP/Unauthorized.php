<?php
namespace Exceptions\HTTP;
class Unauthorized extends HTTPException{
    public $status_code = 401;
    public $name = "Unauthorized";

    function __construct($message, $data = [], $realm = null){
        if($realm === null && app("Auth_logins_enabled")) $realm = app("Auth_login_page");
        $auth_realm = "Basic realm=\"$realm\", charset=\"UTF-8\"";
        header($auth_realm);
        parent::__construct($message,$data);
    }
}