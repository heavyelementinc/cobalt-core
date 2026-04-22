<?php
namespace Cobalt\Auth\Users\Traits;

trait Login {
    const LOGIN_SESSION_STEP_KEY = "loginStep";
    const LOGIN_STEP_USER_DISCOVERY  = 0;
    const LOGIN_STEP_PASSWORD_AUTH   = 1;
    const LOGIN_STEP_TWO_FACTOR_AUTH = 2;
    const LOGIN_STEP_COMPLETE        = 255;
    // const LOGIN_STEP_PASSWORD_RESET  = 3;

    public function login_form() {
        $template = "Cobalt/Auth/Users/templates/login/stage-0-login-form.php";
        switch($_SESSION[self::LOGIN_SESSION_STEP_KEY]) {
            case self::LOGIN_STEP_COMPLETE:
                redirect($_GET['resume'] ?? "/admin/");
                return;
            case self::LOGIN_STEP_TWO_FACTOR_AUTH:
                
                break;
            case self::LOGIN_STEP_PASSWORD_AUTH:
                $template = "";
                break;
            case self::LOGIN_STEP_USER_DISCOVERY:
            default:
                $template = "Cobalt/Auth/Users/templates/login/stage-0-login-form.php";
                break;
        }
        return view($template);
    }

    private function api_login_form_user_discovery($post) {

    }

    public function login_form_password_auth($post) {

    }
}