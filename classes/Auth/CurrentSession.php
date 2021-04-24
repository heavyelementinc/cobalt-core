<?php
namespace Auth;

class CurrentSession{
    /** The CurrentSession class takes the current request's validation cookie and looks
     * up the token in the session database. It runs checks to see if the user's token is
     * still valid.
     * 
     * TODO: Encrypt cookie values in database
     */
    function __construct(){
        $this->cookie_name = app('session_cookie_name'); // Name of the cookie we send to the client

        $this->token_value = (key_exists($this->cookie_name,$_COOKIE)) ? $_COOKIE[$this->cookie_name] : null;
        $this->now = time();
        $this->day = 60*60*24;
        $this->month = $this->day*30;
        $this->default_cookie_expiration = $this->now + $this->month;
        $this->default_token_expiration = $this->now + $this->month;
        $this->default_token_refresh = $this->now + $this->day;
        $this->collection = \db_cursor('sessions');
        $this->cookie_options = [
            'expires' => $this->default_cookie_expiration,
            'path' => '/',
            'domain' => $_SERVER['SERVER_NAME'],
            'secure' => is_secure(),
            'samesite' => true
        ];
        $this->context = ($GLOBALS['route_context'] === "web") ? true : false;
        /** Every client must be assigned a token, we'll use this to update our
         * user account if/when they sign in.
         * 
         * If the token value is null, create a session
         */
        if(!$this->token_value) $this->create_session();

        /** Find the user session token in the token database */
        $this->session = $this->collection->findOne([$this->cookie_name => $this->token_value]);
        /** If the token has expired, we need to update the token */
        if($this->is_expired()) $this->create_session();
        if($this->is_needing_refresh()) $this->update_token();
    }

    function create_session(){
        if(!$this->context) return false;
        $token = $this->get_unique_token();
        $result = $this->collection->insertOne(
            [
                $this->cookie_name => $token,
                'user_id' => null, // You're not gonna be logged in at this point
                'refresh' => $this->default_token_refresh,
                'expires' => $this->default_token_expiration,
                'persist' => true
            ]
        );
        $this->send_session_cookie($token);
    }

    function update_token(){
        if(!$this->context) return false;
        $user_id = $this->session['_id'] ?? (string)session("_id");
        $token = $this->get_unique_token();
        $result = $this->collection->updateOne(
            [$this->cookie_name => $this->token_value],
            [
                '$set' => [
                    'user_id' => $user_id,
                    'refresh' => $this->default_token_refresh,
                ]
            ]
            // We DO NOT want to upsert, here. The session should exist;
        );
        $_COOKIE['csrf_old_token'] = $this->token_value;

        $this->send_session_cookie($token);
    }

    function get_unique_token(){
        $token = \random_string(48);
        $search = $this->collection->count([$this->cookie_name => $token]);
        /** Check if the token is already in use, if it is, let's try again. */
        if($search >= 1) return $this->update_token();
        return $token;
    }

    function login_session($user_id,$stay_logged_in){
        $query = [
            $this->cookie_name => $this->token_value,
            'user_id' => null // We want to make sure we're only updating tokens that aren't logged in
        ];
        $count = $this->collection->count($query);
        if($count === 0) return true;
        $result = $this->collection->updateOne(
            $query,
            ['$set' => [
                'user_id' => (string)$user_id,
                'persist' => filter_var($stay_logged_in, FILTER_VALIDATE_BOOLEAN)
                ]
            ]
        );
        if($result->getModifiedCount() === 0) throw new \Exceptions\HTTP\BadRequest("You're already logged in.");
        return true;
    }
    
    function send_session_cookie($token){
        $this->token_value = $token;
        setcookie($this->cookie_name,$token,$this->cookie_options);
        $_COOKIE[$this->cookie_name] = $token;
    }

    function is_expired(){
        if(!isset($this->session['expires'])) return true;
        if($this->session['expires'] + $this->month <= $this->now) return true;
        return false;
    }
    
    function is_needing_refresh(){
        if(!isset($this->session['refresh'])) return true;
        if($this->session['refresh'] + $this->day <= $this->now) return true;
        return false;
    }

    function send_session_delete(){
        if(!$this->context) return false;
        
        /** Send the header to expire the current cookie */
        setcookie($this->cookie_name,null,$this->now - $this->day,"/");
    }

    function logout_session(){
        $result = $this->collection->updateOne(
            [
                $this->cookie_name => $this->token_value
            ],
            ['$set' => ['user_id' => null]]
        );
        $this->session = null;
        /** Unset the cookie's value */
        unset($_COOKIE[$this->cookie_name]);
        return ['result' => (bool)$result->getModifiedCount()];
    }
}