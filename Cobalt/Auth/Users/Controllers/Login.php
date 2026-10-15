<?php

namespace Cobalt\Auth\Users\Controllers;

use Cobalt\Auth\Session\Models\Session;
use Cobalt\Auth\Users\Models\User;

class Login {
    const CONTINUE_KEY = 'continue';
    const LOGIN_PATH = '/login/';
    const LOGIN_FAILED = 'Invalid username or password';

    function login_page() {
        if(isset($_GET[self::CONTINUE_KEY])) {
            $_SESSION[self::CONTINUE_KEY] = $_GET[self::CONTINUE_KEY];
            redirect(self::LOGIN_PATH);
            exit;
        }

        return view("Cobalt/Auth/Users/templates/login/login-form-basic.php");
    }

    

    function on_login_form_submit() {
        $users = new User();
        $u = $users->findOne([
            '$or' => [
                ['uname' => $_POST['uname']],
                ['email' => $_POST['uname']]
            ]
        ]);
        if(!$u) {
            return view("Cobalt/Auth/Users/templates/login/login-form-basic.php", ['message' => self::LOGIN_FAILED]);
        }
        if(password_verify($_POST['pword'], $u->pword) === false) {
            return view("Cobalt/Auth/Users/templates/login/login-form-basic.php", ['message' => self::LOGIN_FAILED]);
        }
        global $auth;
        $auth->logInUser($u);
        redirect($_SESSION[self::CONTINUE_KEY] ?? "/");
        exit;
    }
}