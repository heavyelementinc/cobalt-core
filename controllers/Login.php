<?php

use Auth\Authentication;
use Auth\UserCRUD;
use Cobalt\Token;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\MethodNotAllowed;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use Exceptions\HTTP\UnknownError;
use Mail\SendMail;



class Login {
    function login_form() {
        $vars = (new Authentication)->generate_login_form();
        add_vars(['title' => "Log in", ...$vars[0]]);
        return view($vars[1]);
    }

    function handle_login() {
        $result = (new Authentication)->handle_login();
        if(__APP_SETTINGS__['Auth_login_mode'] === COBALT_LOGIN_TYPE_STAGES) {
            // exit;
            return;
        }
        // If we're here, we've been logged in successfully, but we may need to
        // perform an additional level of authentcation.
        if($result['login'] === 0) {
            header("X-Modal: /login/2fa");
            return $result;
        }
        
        // Now it's time to determine what we should be doing. If we're on the 
        // login page, redirect the user to "/admin" otherwise refresh the page
        $redirect = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        if (!$redirect) $redirect = "/";
        if ($redirect === app("Auth_login_page") && has_permission('Admin_panel_access')) {
            // If the user has admin panel privs, we redirect them there
            $redirect = app("Admin_panel_prefix") . "/";
            http_response_code(200);
            redirect("$redirect");
            return;
        }
        http_response_code(200);
        header("X-Refresh: @now");
        return $result;
    }

    function handle_email_login_stage_1() {
        $email = Authentication::handle_email_login($_POST['username']);
        header("X-Redirect: /login/email");
    }

    function handle_token_auth($token) {
        $crud = new UserCRUD();
        $user = $crud->findOneAsSchema(['login_tokens.token' => $token]);
        if(!$user) throw new BadRequest("Invalid token");
        $value = null;
        foreach($user->login_tokens as $obj) {
            if($obj['token'] === $token) {
                $value = $obj;
                break;
            }
        }
        if(!$value) throw new BadRequest("Token could not be found");
        $token = new Token($obj['token'], $obj['expires'], $obj['type']);

        // Cleanup tokens
        $expire = $crud->expire_login_token($obj['token']);
                
        $keys = [
            'login' => "handle_email_login_stage_2",
            'reset' => "handle_password_reset_stage_2"
        ];

        if(!$token->is_expired()) throw new Unauthorized("This token has expired");
        if(!key_exists($value['type'], $keys)) throw new UnknownError("This token specifies invalid parameters");

        return $this->{$keys[$value['type']]}($token, $user, $value);
    }

    private function handle_email_login_stage_2($token, $user, $value):never {
        $GLOBALS['auth']->login_user($user->uname, null, ((int)$_GET['stay_logged_in'] == true), true);
        $sanitized = $_GET["continue"];
        header("Location: $sanitized");
        exit;
    }

    private function handle_password_reset_stage_2($token, $user, $value) {

    }

    function email_sent() {
        add_vars([
            'title' => "Email sent"
        ]);
        return view("/authentication/email-sent.html");
    }

    function handle_logout() {
        $result = $GLOBALS['auth']->logout_user();
        header("X-Redirect: /");
        return $result;
    }

    function password_reset_initial_form() {
        // Check if password validation is supported
        if(!app("Mail_smtp_host") || !app("Mail_password")) throw new MethodNotAllowed("This app does not support password reset.");
        if(!app("Auth_allow_password_reset")) throw new MethodNotAllowed("This app does not allow password resets.");
        // Present the user with a form
        add_vars([
            'title' => 'Password Reset'
        ]);
        return view('/authentication/password-reset/reset-form.html');
    }

    function api_password_reset_username_endpoint() {
        // Accept the username
        $query = ['email' => $_POST['email']];
        
        // Check that the username exists
        $crud = new \Auth\UserCRUD();
        /** @var UserPersistance */
        $user = $crud->findOne($query);
        $message = "X-Status: @success We will send you an email if your information is in our database.";
        // If it doesn't, send a failure status
        if(!$user) {
            header($message);
            return 1;
        }
        
        $user->expire_token_type("password-reset");

        // Check that sending mail is supported
        // Generate a token
        $token = $user->generate_token('password-reset');

        // Fire off an email with a password reset token
        $mail = new SendMail();
        $mail->set_vars(['token' => $token]);
        $mail->set_body_template("/authentication/password-reset/reset-email.php");
        try {
            $mail->send($user->email->getValue(), "Password Reset");
        } catch (Exception $e) {
            header("X-Status: @error An error occurred. Please try again later.");
            return;
        }

        // Send a successful message
        header($message);
        return 1;
    }


    function password_reset_token_form($token) {
        $crud = new \Auth\UserCRUD();
        // Check that the token is valid
        $user = $crud->findUserByToken('password-reset', $token);
        // Otherwise, throw a 404
        if(!$user) throw new NotFound("That token either does not exist or has expired.", true);
        // Present the user with a form to create a new password
        add_vars([
            'title' => 'Password Reset',
            // 'token' => $check->token
            'user' => $user,
        ]);

        return view("/authentication/password-reset/new-password-form.php");
    }

    function api_password_reset_password_validation($token) {
        // Check that $token is valid
        $crud = new UserCRUD();
        $user = $crud->findUserByToken('password-reset', $token);
        // If not, throw 404
        if(!$user) throw new NotFound("That token either does not exist or has expired.", true);
        // Validate the new password
        $user->pword = $_POST['password'];
        $hash = $user->pword->getDirective("filter");
        
        // Update the password
        $crud->updateOne(['_id' => $user->_id], [
            '$set' => [
                'pword' => $hash
            ]
        ]);
        // Invalidate token
        $user->expire_token_type('password-reset');
        // Redirect user to login page
        redirect("/login/?message=password_reset");
    }
}
