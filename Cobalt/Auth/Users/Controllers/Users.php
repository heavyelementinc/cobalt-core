<?php
namespace Cobalt\Auth\Users\Controllers;

use Cobalt\Auth\Session\Models\Session;
use Cobalt\Auth\Users\Authentication;
use Cobalt\Auth\Users\Models\User;
use Cobalt\Auth\Users\MultiFactorSchemes\TOTPManager;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\RangeNotSatisfiable;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

class Users extends ModelController {
    static $api_read_permission          = "Auth_allow_editing_users";
    static $api_create_permission        = "Auth_allow_editing_users";
    static $api_update_permission        = "Auth_allow_editing_users";
    static $api_destroy_permission       = "Auth_allow_editing_users";
    static $api_multidestroy_permission  = "Auth_allow_editing_users";
    static $api_batch_archive_permission = "Auth_allow_editing_users";
    static $api_archive_permission       = "Auth_allow_editing_users";
    static $admin_index                  = "Auth_allow_editing_users";
    static $admin_new_document           = "Auth_allow_editing_users";
    static $admin_edit                   = "Auth_allow_editing_users";

    public static function defineModel(): Model {
        return new User();
    }

    public function edit($document): string {
        return view("Cobalt/Auth/Users/templates/admin/user-editor.php");
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Are you sure you want to delete user `<strong>$document->uname</strong>`?",
            'okay' => "Yes",
            'post' => $_POST,
        ];
    }

    const LOGIN_USER_LOGGED_IN_KEY = 'user_is_logged_in';

    const LOGIN_STAGE_KEY = 'login_stage';
    const LOGIN_USER_ID_KEY = 'login_user_id';
    const LOGIN_STAGE_DISCOVER_USER   = 0;
    const LOGIN_STAGE_PASSWORD_AUTH   = 1;
    const LOGIN_STAGE_AUTH_TWO_FACTOR = 2;

    const ERR_INVALID_CREDENTIALS = "Wrong username or password";

    private ?User $user;
    
    private string $errors = "";

    function __construct(?string $name = null){
        parent::__construct($name);
        if(isset($_SESSION[self::LOGIN_USER_ID_KEY])) {
            $this->user = $this->model->findOne([
                '_id' => new ObjectId($_SESSION[self::LOGIN_USER_ID_KEY])
            ]);
        }
    }

    public function userSelfService() {
        
    }

    public function api_list_authenticated_users() {
        $details = [];
        foreach(auth()->getSession()->getArrayOfUsers() as $user) {
            $user = [
                'name' => $user->name(),
                'avatar' => embed_image($user->avatar),
                'unread' => $user->getUnreadNotificationCount(),
            ];
            
            $details[] = $user;
        }
        return $details;
    }

    public function api_switch_to_authenticated_user($value) {
        if(auth()->isUserLoggedIn() === false) throw new Unauthorized("You're not logged in", true);
        $index = (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        if(!is_int($index)) throw new BadRequest("Bad request");
        $session = auth()->getSession();
        if($index > ($session->represents->length() - 1)) throw new RangeNotSatisfiable("No user exists", true);
        $session->setCurrentUserIndex($index);
        return true;
    }

    public function api_logout() {
        if(auth()->isUserLoggedIn() === false) throw new BadRequest("You're not logged in", true);
        auth()->getSession()->logOutUser(auth()->getCurrentSessionUser());
        header("X-Refresh: @now");
    }

    public function magic_link(string $ident, string $pword) {
        if(!$pword) {
            redirect("/login");
            exit;
        }
        $sessions = new Session();
        $discoveredSession = $sessions->findOne(['magic_links.ident' => $ident]);
        if($discoveredSession == null) {
            redirect("/login");
            exit;
        }
        $verified = false;
        foreach($discoveredSession->magic_links as $index => $link) {
            if($link->ident->value !== $ident) continue;
            if(password_verify($pword, $link->pword->value)) {
                $verified = true;
                break;
            }
        }
        if(!$verified) {
            redirect("/login");
            exit;
        }
        
        redirect("/");
        exit;
    }

    public function login_form() {
        if(isset($_GET['reset'])) $_SESSION[self::LOGIN_STAGE_KEY] = self::LOGIN_STAGE_DISCOVER_USER;

        // Get the resume param if it exists and save it in the 
        if($_GET[SESSION_RESUME_PARAM]) {
            // redirect();
            $_SESSION[SESSION_RESUME_PARAM] = $_GET[SESSION_RESUME_PARAM];
            redirect("/login");
            return;
        }
        $login_stage = $_SESSION[self::LOGIN_STAGE_KEY];
        if(!isset($_SESSION[self::LOGIN_USER_LOGGED_IN_KEY])) $_SESSION[self::LOGIN_STAGE_KEY] = self::LOGIN_STAGE_DISCOVER_USER;
        set('title', 'Login');
        // return view("Cobalt/Auth/Users/templates/login/login-form-basic.php");
        switch($login_stage) {
            case self::LOGIN_STAGE_AUTH_TWO_FACTOR:
                return $this->login_stage_web_two_factor_auth();
            case self::LOGIN_STAGE_PASSWORD_AUTH:
                return $this->login_stage_web_password_auth();
            case self::LOGIN_STAGE_DISCOVER_USER:
            default:
                return $this->login_stage_web_discover_user();
        }
    }

    private function login_stage_web_discover_user() {
        return view("Cobalt/Auth/Users/templates/login/stage-0-login-form.php", [
            'errors' => $this->errors
        ]);
    }

    private function login_stage_web_password_auth() {
        return view("Cobalt/Auth/Users/templates/login/stage-1-password-prompt.php", [
            'user' => $this->user,
            'errors' => $this->errors,
        ]);
    }

    private function login_stage_web_two_factor_auth() {
        $userTFAModes = $this->user->getUserTFAModes();
        if($userTFAModes === User::TFA_IS_DISABLED) {
            // Handle skipping TOTP setup
            if(isset($_GET[SESSION_CONTINUE_PARAM])) {
                $this->login_complete();
                exit;
            }
            return view("Cobalt/Auth/Users/templates/login/stage-2-tfa-not-enabled.php",[
                'user' => $this->user,
                'errors' => $this->errors
            ]);
        }
        switch($userTFAModes) {
            default:
                return view("Cobalt/Auth/Users/templates/login/stage-2-tfa.php",[
                    'user' => $this->user,
                    'errors' => $this->errors,
                    'userTFAModes' => $userTFAModes
                ]);
        }
    }

    public function basic_login() {
        $generic_message = "Login failed";
        if(!$_POST['uname']) {
            throw new Unauthorized($generic_message);
        }
        if(!$_POST['pword']) {
            throw new Unauthorized($generic_message);
        }
        unset(
            // $_SESSION[self::LOGIN_USER_LOGGED_IN_KEY],
            $_SESSION[self::LOGIN_USER_ID_KEY],
            $_SESSION[self::LOGIN_STAGE_KEY],
        );

        $user = $this->model->findOne([
            '$or' => [
                ['uname' => $_POST['uname']],
                ['email' => $_POST['uname']]
            ]
        ]);
        if(!$user) {
            throw new Unauthorized($generic_message);
        }
        if(!password_verify($_POST['pword'], $user->pword->value)) {
            throw new Unauthorized($generic_message);
        }
        global $auth;
        $auth->logInUser($this->user);
        redirect($_GET['resume'] ?? "/admin/");
        exit;
    }

    public function api_login_handler() {
        $login_stage = $_SESSION[self::LOGIN_STAGE_KEY];
        switch($login_stage) {
            case self::LOGIN_STAGE_AUTH_TWO_FACTOR:
                update("#login-form-container", ['outerHTML' => $this->login_stage_api_two_factor($_POST)]);
                return;
            case self::LOGIN_STAGE_PASSWORD_AUTH:
                update("#login-form-container", ['outerHTML' => $this->login_stage_api_password_auth($_POST)]);
                return;
            case self::LOGIN_STAGE_DISCOVER_USER:
            default:
                update("#login-form-container", ['outerHTML' => $this->login_stage_api_discover_user($_POST)]);
                return;
        }
        update("#login-form-container", ['outerHTML' => $this->login_form($_POST)]);
    }

    private function login_stage_api_discover_user($post):User|string {
        $_SESSION[self::LOGIN_USER_LOGGED_IN_KEY] = false;
        $this->user = $this->model->findOne([
            '$or' => [
                ['uname' => $post['username']],
                ['email' => $post['username']]
            ]
        ]);
        
        if(!$this->user) {
            $this->errors .= self::ERR_INVALID_CREDENTIALS;
            return $this->login_form();
        }

        $isUserAlreadyInSession = $this->isDiscoveredUserAlreadyInSession();
        if($isUserAlreadyInSession) {
            $this->resume();
            return "";
        }
        
        $_SESSION[self::LOGIN_USER_ID_KEY] = (string)$this->user->_id;
        $_SESSION[self::LOGIN_STAGE_KEY] = self::LOGIN_STAGE_PASSWORD_AUTH;
        return $this->login_form();
    }

    private function isDiscoveredUserAlreadyInSession() {
        $session = auth()->getSession();
        if(!$session) return false;
        foreach($session->getArrayOfUsers() as $index => $user) {
            if((string)$user->_id === (string)$this->user->_id) {
                $session->setCurrentUserIndex($index);
                return true;
            }
        }
        return false;
    }

    private function login_stage_api_password_auth($post) {
        if(!$this->user) {
            $_SESSION[self::LOGIN_STAGE_KEY] = self::LOGIN_STAGE_DISCOVER_USER;
            $this->errors .= "Something went wrong. Please try again.";
            return $this->login_form();
        }
        if(!password_verify($post['password'], $this->user->pword->value)) {
            $this->errors .= "That wasn't right. Try again.";
            return $this->login_form();
        }
        // if($this->user->getUserTFAModes() === 0) {
        // $_SESSION[self::LOGIN_STAGE_KEY] = self::LOGIN_STAGE_AUTH_TWO_FACTOR;
        // return $this->login_form();
        $this->login_complete(true);
        unset($_SESSION[self::LOGIN_STAGE_KEY]);
        redirect($_SESSION[SESSION_RESUME_PARAM] ?? "/");
        // }
        // $this->login_complete();
    }

    private function login_stage_api_two_factor($post) {
        $userTFAEnabled = $this->user->getUserTFAModes();
        if($userTFAEnabled == 0) {
            return $this->login_complete(false);
        }
        $totpManager = new TOTPManager();
        $verified = $totpManager->totp_verify_otp($this->user, $post['totp']);
        if($verified === false) {
            $this->errors .= "That code appears to be invalid";
            return;
        }
        return $this->login_complete(false);
    }

    private function login_complete($automaticallyResume = true){
        if(!$this->user) throw new Exception("An unknown error occurred");
        global $auth;
        $auth->logInUser($this->user);
        unset($_SESSION[SESSION_STAGE_STATE]);
        if($automaticallyResume) $this->resume();
    }

    private function resume() {
        // Handle the login setup
        $resume = ($_SESSION[SESSION_RESUME_PARAM]) ? $_SESSION[SESSION_RESUME_PARAM] : "/admin";
        if($resume) {
            redirect($resume);
            unset($_SESSION[SESSION_RESUME_PARAM]);
            exit;
        }
    }

    public function delete_session($id) {
        
        $_id = new ObjectId($id);
        $session = new Session();
        $session->deleteOne(['_id' => $_id]);
        header("X-Reload: @now");
        exit;
    }
}