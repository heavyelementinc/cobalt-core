<?php
namespace Exceptions\HTTP;

use Auth\Authentication;

class Unauthorized extends HTTPException{
    public $status_code = 401;
    public $name = "Unauthorized";

    function __construct($message, $publicMessage = false, $data = [], $realm = null){
        if($realm === null && app("Auth_logins_enabled")) $realm = app("Auth_login_page");
        $auth_realm = "Basic realm=\"$realm\", charset=\"UTF-8\"";
        header($auth_realm);
        $vars = Authentication::generate_login_form();
        $merge = array_merge($vars[0], $data, ['template' => $vars[1]]);
        parent::__construct($message, $publicMessage, $merge);
    }
}
