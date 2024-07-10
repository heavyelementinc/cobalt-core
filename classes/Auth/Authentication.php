<?php

/** This class handles authenticating the current request.
 * 
 * 
 */

namespace Auth;

use Cobalt\Token;
use DateTime;
use Exception;
use Exceptions\HTTP\BadRequest;
use Mail\SendMail;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use SensitiveParameter;

class Authentication {
    public $permissions = null;
    public $session;
    public $user = null;

    function __construct() {
        if (!app("Auth_user_accounts_enabled")) return false;

        $this->session = new CurrentSession();
        if (isset($this->session->session->user_id)) $ua = new UserCRUD();
        else return $this;
        $this->user = $ua->getUserById($this->session->session->user_id);
        $this->permissions = new Permissions();
        if (!$this->user) {
            $GLOBALS['session'] = null;
            return $this;
        }

        $GLOBALS['session'] = $this->user;
        $GLOBALS['session']['session_data'] = (array)$this->session;
    }

    /** Our user login routine. */
    function login_user($username, #[SensitiveParameter] $password, $stay_logged_in = false, $skip_password_check = false) {
        $stock_message = "Invalid credentials.";
        /** Get our user by their username or email address */
        $ua = new UserCRUD();
        $user = $ua->getUserByUnameOrEmail($username);

        /** If we don't have a user account after our query has run, then the client
         * submitted a username/email address that hasn't been registered, yet. */
        if ($user === null) throw new \Exceptions\HTTP\Unauthorized("No user found", $stock_message);

        /** Allow the user to login via token */
        if(!$skip_password_check) {
            /** Check if our password verification has failed and throw an error if it did */
            if (!\password_verify($password, $user['pword'])) throw new \Exceptions\HTTP\Unauthorized("Password verification failed", $stock_message);
        }

        $login_state = 10;
        if($user['tfa']['enabled']) $login_state = 0;

        /** Update the user's session information */
        $result = $this->session->login_session($user['_id'], $stay_logged_in, $login_state);

        /** If the session couldn't be updated, we throw an error */
        if (!$result) throw new \Exceptions\HTTP\BadRequest("The session could not be updated", $stock_message);

        // If $loginState === 0, send an update() for TFA login

        return [
            'login' => $login_state
        ];
    }

    /** Our user logout routine */
    function logout_user() {
        // $this->send_session_delete();
        return $this->session->logout_session();
    }

    /**
     * Check if the current user has a permission.
     * 
     * This routine checks if a user is logged in and will throw an 
     * HTTP\Unauthorized Exception if they are not logged in. It will then check
     * if the permission is valid and throw an Exception if it is not.
     * 
     * @throws \Exceptions\HTTP\Unauthorized if not logged in
     * @throws Exception if the permission specified does not exist
     * 
     * @param  string|true $perm_name the name of the permission to check for OR
     *                     a boolean true to confirm an authenticated session
     *                     exists
     * @param  string|null $group the group name or list of group names. 
     *                      Can be null.
     * @param  null|MongoDocument $user if null, the current session will be used
     * @return bool true if the user has permission, false otherwise
     */
    function has_permission($permission, $group = null, $user = null, $throw_no_session = true) {
        if ($user === null) $user = $this->user;
        if ($group === null) $group = $this->permissions->valid[$permission]['group'];

        // If the user is not logged in, they obviously don't have permission
        if ($throw_no_session === false && !$user) return false;
        if (!$user) throw new \Exceptions\HTTP\Unauthorized("No authenticated user", "You're not logged in.", ['login' => true]);

        // If the permission is a boolean true AND we've made it here, we're 
        // logged in, so we're good to go, right?
        if ($permission === true) return true;

        // If the permission is NOT valid, we throw an exception.
        if (!isset($this->permissions->valid[$permission])) throw new \Exception("The \"$permission\" permission cannot be validated!");

        // If the app allows root users AND the user belongs to the root group, 
        // they have permission no matter what
        if (app('Auth_enable_root_group') && in_array('root', (array)$user->groups)) return true;

        // If user account requires a password reset, a PasswordUpdateRequired
        // error is thrown.
        if (($user->flags['password_reset_required'] ?? false) === true && $user === $this->user) throw new \Exceptions\HTTP\PasswordUpdateRequired("This authenticated user has the 'password reset' flag set, a password reset is required.", "You must update your password.");

        // app('Auth_require_verified_status') && 

        // Check if user permissions is a BSONDocument or not
        $user_permissions = $user->permissions;
        // If it is a BSONDocument, get an array copy
        if($user_permissions instanceof BSONDocument) $user_permissions = $user_permissions->getArrayCopy();
        
        // If the user account stores the permission, we return that value, 
        // whatever it may be
        if (key_exists($permission, $user_permissions)) return $user_permissions[$permission];

        // If the permission's default value is true, we return true.
        if ($this->permissions->valid[$permission]['default']) return true;

        // If NO group is specified, return the permission's default value
        if ($group === null) return $this->permissions->valid[$permission]['default'];

        // Check if the user HAS the group in their groups
        if (in_array($group, (array)$user->groups)) return true;

        // Check if this permission belongs to the group specified
        if (isset($this->permissions->valid[$permission]['group'][$group])) return $this->permissions->valid[$permission]['group'][$group];

        // If we've made it here, we *probably* don't have the permission.
        return false;
    }

    function destroy_expired_tokens() {
        
    }

    static function handle_login() {
        $auth = null;
        $stock_message = "Request is missing valid credentials";
        // Check if the authentication values exist
        if (app('API_authentication_mode') === "headers") {
            try{
                $auth = getHeader("Authentication");
            } catch (Exception $e) {
                throw new BadRequest("No 'Authentication' header found.", $stock_message);
            }
            // Decode and split the credentials
            $credentials = explode(":", base64_decode($auth));
        } else {
            // if (!key_exists('Authentication', $_POST)) throw new BadRequest("The POST body is missing the 'Authentication' field", $stock_message);
            // $auth = $_POST['Authentication'];
            $credentials = [$_POST['username'], $_POST['password']];
        }

        // Log in the user using the credentials provided. If invalid credentials
        // then login_user will throw an exception.
        $result = $GLOBALS['auth']->login_user($credentials[0], $credentials[1], $_POST['stay_logged_in']);
        return $result;
    }

    static function handle_email_login($email) {
        $uname = $email;
        if(!$uname) throw new BadRequest("Username field was not specified","Username is missing");

        $crud = new UserCRUD();
        $user = $crud->getUserByUnameOrEmail($uname);
        if(!$user) {
            sleep(1);
            return;
        }

        $email = new SendMail();

        $tk = $crud->set_token($user['_id'], 'login');

        $email->set_body_template("/emails/email-login.html");

        $path = parse_url($_SERVER['HTTP_REFERER'],PHP_URL_PATH);

        $email->set_vars([
            'token' => $tk->get_token(),
            'user' => $user,
            'current_host' => $_SERVER['COBALT_TRUSTED_HOST'],
            'current_domain' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['COBALT_TRUSTED_HOST'],
            'query' => http_build_query([
                'continue' => $path,
                'stay_logged_in' => $_POST['stay_logged_in']
            ]),
        ]);
        $email->send($user['email'],"Login to " . app("app_short_name"));
    }

    static function generate_login_form():array {
        add_vars(['title' => 'Login']);
        $login = "/authentication/login.html";
        if (!key_exists('HTTPS', $_SERVER) && !app("Auth_enable_insecure_logins")) {
            $login = "/authentication/no-login.html";
        }

        if(app("Auth_login_via_email_token")) {
            if(app("Mail_username") && app("Mail_password") && app("Mail_smtp_host")) {
                $login = "/authentication/login-email-token.html";
            }
        }

        if (app("Auth_account_creation_enabled")) {
            $vars = [
                'create_account' => "<hr>\n<a href='" . app("Auth_onboading_url") . "'>Sign up</a>"
            ];
        }

        return [$vars ?? [], $login];
    }

    static function generate_onboarding_form(): array {
        return [];
    }
}
