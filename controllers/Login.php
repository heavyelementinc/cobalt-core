<?php

use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\MethodNotAllowed;
use Exceptions\HTTP\NotFound;
use Mail\SendMail;

class Login {
    function login_form() {
        add_vars(['title' => 'Login']);
        $login = "/authentication/login.html";
        if (!key_exists('HTTPS', $_SERVER) && !app("Auth_enable_insecure_logins")) {
            $login = "/authentication/no-login.html";
        }

        if (app("Auth_account_creation_enabled")) {
            add_vars(['create_account' => "<hr>\n<a href='" . app("Auth_onboading_url") . "'>Sign up</a>"]);
        }

        set_template($login);
    }

    function handle_login() {
        // Get the headers
        $headers = apache_request_headers();
        // Check if the authentication values exist
        if (!key_exists('Authentication', $headers)) throw new BadRequest("Request is missing Authentication");

        // Decode and split the credentials
        $credentials = explode(":", base64_decode($headers['Authentication']));

        // Log in the user using the credentials provided. If invalid credentials
        // then login_user will throw an exception.
        $result = $GLOBALS['auth']->login_user($credentials[0], $credentials[1], $_POST['stay_logged_in']);

        // If we're here, we've been logged in successfully. Now it's time to
        // determine what we should be doing. If we're on the login page, 
        // redirect the user to "/admin" otherwise refresh the page
        $redirect = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        if (!$redirect) $redirect = "/";
        if ($redirect === app("Auth_login_page") && has_permission('Admin_panel_access')) {
            // If the user has admin panel privs, we redirect them there
            $redirect = app("Admin_panel_prefix") . "/";
        }
        http_response_code(200);
        header("X-Redirect: $redirect");
        return $result;
    }

    function handle_logout() {
        return $GLOBALS['auth']->logout_user();
    }

    function password_reset_initial_form() {
        // Check if password validation is supported
        if(!app("Mail_smtp_host") || !app("Mail_password")) throw new MethodNotAllowed("This app does not support password reset.");
        if(!app("Auth_allow_password_reset")) throw new MethodNotAllowed("This app does not allow password resets.");
        // Present the user with a form
        add_vars([
            'title' => 'Password Reset'
        ]);
        set_template('/authentication/password-reset/reset-form.html');
    }

    function api_password_reset_username_endpoint() {
        // Accept the username
        $query = [
            '$or' => [
                'username' => $_POST['username'],
                'email' => $_POST['username']
            ]
        ];
        
        // Check that the username exists
        $crud = new \Auth\UserCRUD();
        $user = $crud->findOneAsSchema($query);
        
        // If it doesn't, send a failure status
        if(!$user) throw new BadRequest("Resource does not exist.");
        
        // Check that sending mail is supported
        
        // Generate a token
        $token = $user->generate_token('password-reset', );

        // Fire off an email with a password reset token
        $mail = new SendMail();
        $mail->set_vars(['token' => $token]);
        $mail->set_body_template("/authentication/password-reset/reset-email.html");

        // Send a successful message
    }


    function password_reset_token_form($token) {
        // Check that the token is valid
            // Otherwise, throw a 404
        // Present the user with a form to create a new password
    }

    function api_password_reset_password_validation($token) {
        // Check that $token is valid
            // If not, throw 404
        // Validate the new password
        // Update the password
        // Invalidate token
        // Redirect user to login page
    }
}
