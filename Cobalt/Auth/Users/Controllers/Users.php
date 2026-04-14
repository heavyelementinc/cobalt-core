<?php
namespace Cobalt\Auth\Users\Controllers;

use Cobalt\Auth\Users\Authentication;
use Cobalt\Auth\Users\Models\User;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

class Users extends ModelController {
    public static function defineModel(): Model {
        return new User();
    }

    public function edit($document): string {
        return view("Cobalt/Auth/Users/templates/admin/user-editor.php");
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Are you sure you want to delete <strong>$document->uname</strong>?",
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

    public function login_form() {
        // Get the resume param if it exists and save it in the 
        if($_GET[SESSION_RESUME_PARAM]) $_SESSION[SESSION_RESUME_PARAM] = $_GET[SESSION_RESUME_PARAM];
        $login_stage = (isset($_GET[self::LOGIN_STAGE_KEY])) ? (int)$_GET[self::LOGIN_STAGE_KEY] : $_SESSION[self::LOGIN_STAGE_KEY];
        // Let's ensure we're not blindly trusting the user-supplied login stage
        if(!isset($_SESSION[self::LOGIN_USER_LOGGED_IN_KEY])) $_SESSION[self::LOGIN_STAGE_KEY] = self::LOGIN_STAGE_DISCOVER_USER;
        set('title', 'Login');
        switch($login_stage) {
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

    public function api_login_handler() {
        $login_stage = $_POST[self::LOGIN_STAGE_KEY] ?? $_SESSION[self::LOGIN_STAGE_KEY];
        switch($login_stage) {
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
        
        $_SESSION[self::LOGIN_USER_ID_KEY] = (string)$this->user->_id;
        $_SESSION[self::LOGIN_STAGE_KEY] = self::LOGIN_STAGE_PASSWORD_AUTH;
        return $this->login_form();
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
        $this->login_complete();
    }

    private function login_complete(){
        if(!$this->user) throw new Exception("An unknown error occurred");
        global $auth;
        $auth->logInUser($this->user);
        // Handle the login setup
        $resume = ($_SESSION[SESSION_RESUME_PARAM]) ? $_SESSION[SESSION_RESUME_PARAM] : "/admin";
        if($resume) {
            redirect($resume);
            unset($_SESSION[SESSION_RESUME_PARAM]);
        }
    }
}