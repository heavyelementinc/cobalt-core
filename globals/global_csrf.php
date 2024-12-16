<?php

use Exceptions\HTTP\HTTPException;
use Random\RandomException;

/** ==============================================
 *  Cross-Site Request Forgery Mitigation Routines
 *  ============================================== 
 */
const CSRF_TOKEN_EXPIRES = 60 * 60 * 4; // Four hours in seconds
// const CSRF_GRACE_TIME = 
const CSRF_TOKEN_KEY = 'csrf_token';
const CSRF_EXPIRE_KEY = 'csrf_expires';
const CSRF_PREV_KEY = 'csrf_previous';
const CSRF_INCOMING_HEADER = 'X-Mitigation';
const CSRF_INCOMING_FIELD = 'authentication';

/**
 * Will always return a valid token.
 * @return string 
 * @throws RandomException 
 */
function csrf_get_token():string {
    if(csrf_is_expired($_SESSION[CSRF_EXPIRE_KEY])) {
        csrf_generate_token();
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

/**
 * Generates a random string and stores it as the current CSRF token
 * @return string 
 * @throws RandomException 
 */
function csrf_generate_token():string {
    $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(18));
    $_SESSION[CSRF_EXPIRE_KEY] = time();
    return $_SESSION[CSRF_TOKEN_KEY];
}

/**
 * Securely evaluates if the current token is valid
 * @param mixed $token 
 * @return bool 
 */
function csrf_is_valid(?string $token):bool {
    if(!$token) return false;
    if(hash_equals($_SESSION[CSRF_TOKEN_KEY], $token)) {
        return !csrf_is_expired($_SESSION[CSRF_EXPIRE_KEY]);
    }
    return false;
}

/**
 * Securely checks if the token is expired
 * @param mixed $token 
 * @return bool 
 */
function csrf_is_expired(?int $time):bool {
    $now = time();
    $time_diff = $now - $time;
    if($time_diff <= CSRF_TOKEN_EXPIRES) return false;
    return true;
}

// function csrf_get_token():string {
//     csrf_expire_token();
//     if(!$_SESSION['csrf_tokens']) $_SESSION['csrf_tokens'] = [];
//     if($_SESSION['csrf_token_previous']) 

//     $token = [
//         'expires' => strtotime("+2 hours"),
//         'token' => app('csrf_seed') . random_string(16)
//     ];

//     $token_name = csrf_get_unique_token_name();

//     $_SESSION['csrf_token'] = $token;
    
//     return "$token_name:".str_replace('$2y$10$', "", password_hash($token['token'], PASSWORD_BCRYPT));
// }

/**
 * @deprecated
 * @return string 
 * @throws RandomException 
 */
function csrf_get_unique_token_name() {
    // $token = random_string(rand(8,13));
    // if(key_exists($token, $_SESSION['csrf_tokens'])) return csrf_get_unique_token_name();
    return csrf_get_token();
}

/**
 * @deprecated
 * @param mixed $toValidate 
 * @return bool 
 */
function csrf_validate_token($toValidate) {
    // csrf_expire_token();
    // [$name, $token] = explode(":",$toValidate);
    // if(!$name || !$token) throw new HTTPException("Failed CSRF validation");
    // if(!key_exists($name, $_SESSION['csrf_tokens'])) throw new HTTPException("Failed CSRF validation");
    // return password_verify($_SESSION['csrf_tokens'][$name]['token'], "$2y$10$" . $token);
    return csrf_is_valid($toValidate);
}

/**
 * @deprecated
 * @return void 
 */
function csrf_expire_token() {
    throw new Exception("This function is no longer secure to call!");
    // $time = time();
    // if($_SESSION['csrf_tokens']['current']['expires'] <= $time) {
    //     $_SESSION['csrf_tokens']['previous'] = $_SESSION['csrf_tokens']['current'];
    //     $_SESSION['csrf_tokens']['current'] = null;
    // }
}


/** 
 * Returns a CSRF Token
 * @return string CSRF Token
 * */
// function get_csrf_token() {
//     return str_replace('$2y$10$', "", password_hash(csrf_session_token(), PASSWORD_BCRYPT));
// }

/** 
 * Validate our supplied CSRF token 
 * @deprecated
 * @throws Exception if no cookie is specified
 * @param string $token A CSRF token generated by get_csrf_token()
 */
function validate_csrf_token($token) {
    return csrf_is_valid($token);
    /** We get our raw CSRF token */
    // $raw_text_seed = csrf_session_token();
    // if ($raw_text_seed === "") throw new Exception("No cookie specified");
    // /** We set our token string back to a token */
    // $password_string = '$2y$10$' . $token;
    // /** Verify our password */
    // return password_verify($raw_text_seed, $password_string);
}

/** Returns a raw CSRF token (unencrypted)
 * @deprecated
 * @return string Unencrypted CSRF token
 */
function csrf_session_token() {
    if (key_exists('csrf_old_token', $_COOKIE)) return app('csrf_seed') . $_COOKIE['csrf_old_token'];
    // Add "was updated" check
    if (!isset($_COOKIE[app('session_cookie_name')])) return app('csrf_seed');
    return app('csrf_seed') . $_COOKIE[app('session_cookie_name')];
}

/** Handle token expiration. This function should return the same value until a
 * set time has passed.
 * @deprecated
 */
function csrf_token_date() {
    /** TODO: TOKEN EXPIRATION */
    return (string)round(time(), -5);
}

/** Add a CSRF Token element to any template with @csrf_token(); 
 * @return string  HTML hidden input named csrf_token with its value set to 
 *                 the result of get_csrf_token()
 */
function csrf_token() {
    return "<input type='hidden' name='".CSRF_INCOMING_FIELD."' value='" . csrf_get_token() . "'>";
}

/** Add a CSRF token as an attribute to any template with @csrf_attribute(); 
 * @return string - Token Attribute
 */
function csrf_attribute() {
    return "token=\"" . csrf_get_token() . "\"";
}