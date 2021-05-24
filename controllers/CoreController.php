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

    function user_manager($user) {
        $ua = new \Auth\UserAccount();
        $user = (array)$ua->get_user_by_uname_or_email($user);
        if (!$user) throw new \Exceptions\HTTP\NotFound("That user doesn't exist.", ['template' => 'errors/404_invalid_user.html']);
        add_vars([
            'title' => "$user[fname] $user[lname]",
            'user_account' => $user,
            'user_id' => (string)$user['_id'],
            'permission_table' => $GLOBALS['auth']->permissions->get_permission_table($user)
        ]);
        add_template("/authentication/user-management/individual-user.html");
    }
}
