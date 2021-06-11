<?php
class CoreController extends \Controllers\Pages {
    function index() {
    }
    function login() {
        add_vars(['title' => 'Login']);
        $login = "/authentication/login.html";
        if (!key_exists('HTTPS', $_SERVER) && !app("Auth_enable_insecure_logins"))
            $login = "/authentication/no-login.html";

        if (app("Auth_account_creation_enabled"))
            add_vars(['create_account' => "<hr>\n<a href='" . app("Auth_onboading_url") . "'>Sign up</a>"]);

        add_template($login);
    }
}
