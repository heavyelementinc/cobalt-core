<?php

/** This class handles authenticating the current request.
 * 
 * 
 */

namespace Auth;

use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\Token;
use DateTime;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use Mail\SendMail;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use SensitiveParameter;


class Authentication {
    public $permissions = null;
    public $session;
    public $user = null;
    public $messages = [
        'backups_exhausted' => "You just used your last backup key. We've disabled TOTP on your account. You'll need to manually re-enable it.",
        'unauthorized' => "You need to log in before you can access this content.",
        'password_reset' => "Your password has been reset. Please sign in.",
    ];

    function __construct() {
        if (!app("Auth_user_accounts_enabled")) return false;

        $this->session = new CurrentSession();
        $this->permissions = new Permissions();
        if (isset($this->session->session->user_id)) $ua = new UserCRUD();
        else return $this;
        $this->user = $ua->getUserById($this->session->session->user_id);
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
        if($user->tfa?->enabled === TFA_STATE_ENABLED) $login_state = 0;

        return $this->store_user_session($user['_id'], $stay_logged_in, $login_state);
    }

    private function store_user_session(ObjectId $user_id, bool $stay_logged_in, $tfa_state) {
        /** Update the user's session information */
        $result = $this->session->login_session($user_id, $stay_logged_in, $tfa_state);

        /** If the session couldn't be updated, we throw an error */
        if (!$result) throw new \Exceptions\HTTP\BadRequest("The session could not be updated", "Invalid credentials");

        // If $loginState === 0, send an update() for TFA login

        return [
            'login' => $tfa_state
        ];
    }

    /** Our user logout routine */
    function logout_user() {
        // $this->send_session_delete();
        unset($_SESSION[SESSION_USER_ID], $_SESSION[SESSION_STAGE_STATE], $_SESSION[SESSION_STAY_LOGGED_IN], $_SESSION[SESSION_TFA_STATE]);
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
     * @param  string|null $group deprecated
     * @param  null|MongoDocument $user if null, the current session will be used
     * @return bool true if the user has permission, false otherwise
     */
    function has_permission($permission, $isRoot = null, $user = null, $throw_no_session = true) {
        if ($user === null) $user = $this->user;
        if ($user instanceof BSONDocument) throw new Exception("Detected obsolete user details. Please run the upgrade process.", true);

        // If the user is not logged in, they obviously don't have permission
        if ($throw_no_session === false && !$user) return false;
        if (!$user) throw new \Exceptions\HTTP\Unauthorized("No authenticated user", "You're not logged in.", ['login' => true]);

        // If the permission is a boolean true AND we've made it here, we're 
        // logged in, so we're good to go, right?
        if ($permission === true) return true;

        // If the permission is NOT valid, we throw an exception.
        if (!isset($this->permissions->valid[$permission])) throw new \Exception("The \"$permission\" permission cannot be validated!");

        // If user account requires a password reset, a PasswordUpdateRequired
        // error is thrown.
        if (($user->flags->key_exists('password_reset_required') && $user->flags->password_reset_required->getValue() ?? false) === true && $user === $this->user) throw new \Exceptions\HTTP\PasswordUpdateRequired("This authenticated user has the 'password reset' flag set, a password reset is required.", "You must update your password.");

        // If the app allows root users AND the user belongs to the root group, 
        // they have permission no matter what
        if (__APP_SETTINGS__['Auth_enable_root_group'] && $user->is_root->getValue() === true) return true;

        // app('Auth_require_verified_status') && 

        // Check if the user has a given permission set, if they do, return that value.
        $user_permissions = (array)$user->permissions->getValue();
        if (key_exists($permission, $user_permissions)) {
            // if($user_permissions[$permission] instanceof BSONDocument) return $user_permissions[$permission];
            return $user_permissions[$permission];
        }


        // If the permission's default value is true, we return true.
        if ($this->permissions->valid[$permission]['default']) return true;

        return $this->permissions->valid[$permission]['default'];
        // If NO group is specified, return the permission's default value
        // if ($group === null) 

        // Check if the user HAS the group in their groups
        // if (in_array($group, (array)$user->groups)) return true;

        // Check if this permission belongs to the group specified
        // if (isset($this->permissions->valid[$permission]['group'][$group])) return $this->permissions->valid[$permission]['group'][$group];

        // If we've made it here, we *probably* don't have the permission.
        return false;
    }

    function destroy_expired_tokens() {
        
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

    function generate_login_form():array {
        if(__APP_SETTINGS__['Auth_login_mode'] === COBALT_LOGIN_TYPE_STAGES) {
            if(isset($_GET['reset'])) {
                $_SESSION[SESSION_STAGE_STATE] = AUTH_STAGE_0_USER_ACCOUNT_DISCOVERY;
                $_SESSION[SESSION_USER_ID] = null;
            }
            switch($_SESSION[SESSION_STAGE_STATE] ?? AUTH_STAGE_0_USER_ACCOUNT_DISCOVERY) {
                case AUTH_STAGE_0_USER_ACCOUNT_VERIFIED:
                    $this->header_reload_command($this->auth_stage_login_check());
                    exit;
                case AUTH_STAGE_1_USER_AUTHENTICATION:
                    return $this->login_stage_1_authenticate();
                case AUTH_STAGE_2_USER_SECOND_STAGE_VERIFY:
                    return $this->login_stage_2_tfa();
                case AUTH_STAGE_0_USER_ACCOUNT_DISCOVERY:
                default:
                    return $this->login_stage_0_discover_user();
            }
            return [];
        }
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

    private function login_stage_0_discover_user():array {
        if(!is_secure() && !__APP_SETTINGS__['Auth_enable_insecure_logins']) {
            throw new BadRequest(AUTH_PROCESS_ERROR__INSECURE_LOGIN_DISALLOWED, true);
        }

        $view = "/authentication/login/stage-0-discover-user.php";
        $vars = ['message' => $this->messages[$_GET['message'] ?? '']];
        return [$vars, $view];
    }
    private function login_stage_1_authenticate():array {
        $view = "/authentication/login/stage-1-password-prompt.php";
        if(__APP_SETTINGS__['Auth_login_via_email_token']) {
            if(app("Mail_username") && app("Mail_password") && app("Mail_smtp_host")) {
                $view = "/authentication/login/stage-1-password-or-email.php";
            }
        }
        $vars = [
            'user' => (new UserCRUD())->getUserById(new ObjectId($_SESSION[SESSION_USER_ID])),
            'message' => $this->messages[$_GET['message'] ?? '']
        ];
        return [$vars, $view];
    }
    private function login_stage_2_tfa():array {
        $view = "/authentication/login/stage-2-tfa.php";
        $user = (new UserCRUD())->getUserById(new ObjectId($_SESSION[SESSION_USER_ID]));
        if(!$user) throw new NotFound(AUTH_PROCESS_ERROR__USER_NOT_FOUND);

        $resume = urldecode($_GET[SESSION_RESUME_PARAM] ?? "");

        // If 2FA is not enabled, then the user should be logged in at this point
        // so let's do that.
        if(!$user->tfa?->enabled) {
            $view = "/authentication/login/stage-2-tfa-not-enabled.php";
            $_SESSION[SESSION_STAGE_STATE] = AUTH_STAGE_0_USER_ACCOUNT_VERIFIED;
            $resume = $this->auth_stage_login_check();
        }
        $vars = [
            'user' => $user, 
            'resume' => $resume ? $resume : "/admin/",
            'message' => $this->messages[$_GET['message'] ?? '']
        ];
        return [$vars, $view];
    }

    function handle_login() {
        if(__APP_SETTINGS__['Auth_login_mode'] === COBALT_LOGIN_TYPE_STAGES) {
            switch($_SESSION[SESSION_STAGE_STATE] ?? AUTH_STAGE_0_USER_ACCOUNT_DISCOVERY) {
                case AUTH_STAGE_0_USER_ACCOUNT_DISCOVERY:
                    return $this->auth_stage_0_discover_user($_POST["username"]);
                case AUTH_STAGE_1_USER_AUTHENTICATION:
                    return $this->auth_stage_1_authenticate($_POST["password"]);
                case AUTH_STAGE_2_USER_SECOND_STAGE_VERIFY:
                    return $this->auth_stage_2_tfa($_POST["totp"]);
                default:
                    header("X-Redirect: /login/?referer=".urlencode($_SERVER['HTTP_REFERER']));
            }
            return;
        }
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

    private function auth_stage_0_discover_user(string $username_or_email_address) {
        $ua = new UserCRUD();
        $result = $ua->getUserByUnameOrEmail($username_or_email_address);
        if(!$result) throw new NotFound(AUTH_PROCESS_ERROR__USER_NOT_FOUND, true);
        http_response_code(200);
        $_SESSION[SESSION_USER_ID] = (string)$result->_id;
        $_SESSION[SESSION_STAGE_STATE] = AUTH_STAGE_1_USER_AUTHENTICATION;
        $this->header_reload_command();
    }

    private function auth_stage_1_authenticate(?string $password) {
        if(!$password) {
            update(".error", ['innerText' => AUTH_PROCESS_ERROR__PASSWORD_CANT_BE_BLANK]);
            return;
        }
        // Let's load our user account again
        $ua = new UserCRUD();
        $result = $ua->getUserById(new ObjectId($_SESSION[SESSION_USER_ID]));
        if(!$result) throw new NotFound("User no longer exists", AUTH_PROCESS_ERROR__USER_NOT_FOUND);

        // Let's verify our password
        $check = password_verify($password, (string)$result->pword);
        if(!$check) {
            // If the password is wrong, let's tell the user by updating the client
            update(".error", ['innerText' => AUTH_PROCESS_ERROR__PASSWORD_HASH_FAIL]);
            return;
        }

        // If we're here, we know we've verified our password.

        // Let's store if we want to keep our user account logged in or not
        $_SESSION[SESSION_STAY_LOGGED_IN] = $_POST['stay_logged_in'];

        // Now, let's check if TFA is enabled and check if the user has it enabled
        if(__APP_SETTINGS__['TwoFactorAuthentication_enabled'] 
            && ($result->tfa?->enabled === true || __APP_SETTINGS__['TwoFactorAuthentication_nag_unenrolled_users'])
        ) {
            $_SESSION[SESSION_STAGE_STATE] = AUTH_STAGE_2_USER_SECOND_STAGE_VERIFY;
        } else {
            // Otherwise, we're logged in.
            $_SESSION[SESSION_STAGE_STATE] = AUTH_STAGE_0_USER_ACCOUNT_VERIFIED;

        }
        $this->header_reload_command($this->auth_stage_login_check());
    }

    private function auth_stage_2_tfa(?string $tfa) {
        if(!$tfa) {
            update(".error", ["innerText" => AUTH_PROCESS_ERROR__TFA_CANNOT_BE_BLANK]);
            return;
        }
        // Look up our user
        $ua = new UserCRUD();
        $result = $ua->getUserById(new ObjectId($_SESSION[SESSION_USER_ID]));
        
        // Check that the user exists
        if(!$result) throw new NotFound("User no longer exists", AUTH_PROCESS_ERROR__USER_NOT_FOUND);
        
        // Verify the supplied OTP
        $mfa = new MultiFactorManager();

        if(strlen($tfa) === 6) {
            $verify_opt = $mfa->verify_otp($result, $tfa);
        } else {
            $verify_opt = $mfa->verify_backup_code($result, $tfa);
        }

        if(!$verify_opt) {
            // If the OTP is invalid, notify the end user
            update(".error", ["innerText" => AUTH_PROCESS_ERROR__TFA_VERIFY_FAILURE]);
            return;
        }

        // If we're here, OTP verification has succeeded!
        // Let's update the session state
        $_SESSION[SESSION_STAGE_STATE] = AUTH_STAGE_0_USER_ACCOUNT_VERIFIED;
        $this->header_reload_command($this->auth_stage_login_check());
    }

    function header_reload_command($target = null) {
        if($target === false || $target === null) {
            $resume = urldecode($_GET[SESSION_RESUME_PARAM]?? "") ?? $_SERVER['HTTP_REFERER'];
            $target = "/login/?".SESSION_RESUME_PARAM."=".urlencode($resume);
        }
        redirect($target);
    }

    function auth_stage_login_check() {
        // Let's check if the session is logged in
        if($_SESSION[SESSION_STAGE_STATE] === AUTH_STAGE_0_USER_ACCOUNT_VERIFIED) {
            // If we're logged in, we'll store the session
            $this->store_user_session(new ObjectId($_SESSION[SESSION_USER_ID]), $_SESSION[SESSION_STAY_LOGGED_IN], $_SESSION[SESSION_TFA_STATE]);
            $resume = ($_GET[SESSION_RESUME_PARAM]) ? $_GET[SESSION_RESUME_PARAM] : "";
            // And lets set our target to be the resume property
            return ($resume) ? urldecode($resume) : "/admin/";
        }
        return false;
    }



    static function generate_onboarding_form(): array {
        return [];
    }
}
