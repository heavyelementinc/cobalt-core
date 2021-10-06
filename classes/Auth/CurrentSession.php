<?php

/**
 * CurrentSession
 * 
 * Manages the session information. This sets the browser cookies and handles the
 * storage, lookup and logging in of sessions.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Auth;

use Exception;

class CurrentSession extends \Drivers\Database {
    /* The CurrentSession class takes the current request's validation cookie and looks
      up the token in the session database. It runs checks to see if the user's token is
      still valid.
      
      TODO: Encrypt cookie values in database
     */
    function __construct() {
        parent::__construct();
        $this->cookie_name = app('session_cookie_name'); // Name of the cookie we send to the client

        $this->token_value = (key_exists($this->cookie_name, $_COOKIE)) ? $_COOKIE[$this->cookie_name] : null;
        $this->now = time();
        $this->day = 60 * 60 * 24;
        $this->month = $this->day * 30;

        $this->default_cookie_expiration = $this->now + $this->month;
        $this->default_token_expiration = $this->now + $this->month;
        $this->default_token_refresh = $this->now + $this->day;

        $headers = apache_request_headers();
        $this->cookie_options = [
            'expires' => $this->default_cookie_expiration,
            'path' => '/',
            'domain' => $_SERVER['HTTP_X_FORWARDED_SERVER'] ?? $_SERVER['SERVER_NAME'],
            'secure' => $headers['X-Forwarded-Proto'] ?? app("session_secure_status"),
            'samesite' => true
        ];

        // $this->context = ($GLOBALS['route_context'] === "web" || $GLOBALS['route_context'] === "admin") ? true : false;

        // Check if we are allowed to update the token (web or API are the only context allowed)
        $this->context = app("context_prefixes")[$GLOBALS['route_context']]['session_refresh'];

        /* Every client must be assigned a token, we'll use this to update our
          user account if/when they sign in.
          
          If the token value is null, create a session
         */
        if (!$this->token_value) $this->create_session_cookie();

        /** Find the user session token in the token database */
        $this->session = $this->findOne([$this->cookie_name => $this->token_value]);

        if ($this->session === null) return $this;

        /** If the token has expired, we need to update the token */
        if ($this->is_expired()) $this->create_session_cookie();
        if ($this->is_needing_refresh()) $this->update_token();
    }

    public function get_collection_name() {
        return "sessions";
    }

    function create_session_cookie() {
        if (!$this->context) return false;
        $token = $this->get_unique_token();

        $this->send_session_cookie($token);
    }

    function update_token() {
        if (!$this->context) return false;

        $token = $this->get_unique_token();
        $extend = [];
        if ($this->session->persist) {
            $extend = ['expires' => $this->default_cookie_expiration];
        }

        try {
            $result = $this->updateOne(
                [$this->cookie_name => $this->token_value],
                [
                    '$set' => array_merge([
                        $this->cookie_name => $token,
                        'refresh' => $this->default_token_refresh,
                    ], $extend)
                ]
                // We DO NOT want to upsert, here. The session should exist;
            );
        } catch (\Exception $e) {
            throw new Exception("Could not update session token");
        }
        if ($result->getModifiedCount() !== 1) throw new Exception("Session token was not modified");
        $_COOKIE['csrf_old_token'] = $this->token_value;
        $this->token_value = $token;

        $this->send_session_cookie($token);
    }

    function get_unique_token() {
        $token = \random_string(48);
        try {
            $search = $this->count([$this->cookie_name => $token]);
        } catch (\Exception $e) {
        }
        /** Check if the token is already in use, if it is, let's try again. */
        if ($search >= 1) return $this->update_token();
        return $token;
    }

    function login_session($user_id, $stay_logged_in) {
        // $query = [
        //     $this->cookie_name => $this->token_value,
        //     'user_id' => null // We want to make sure we're only updating tokens that aren't logged in
        // ];
        // $count = $this->count($query);
        // if ($count === 0) return true;
        if (empty($this->token_value)) throw new \Exceptions\HTTP\BadRequest("No token");
        try {
            $result = $this->updateOne(
                [$this->cookie_name => $this->token_value],
                ['$set' => [
                    $this->cookie_name => $this->token_value,
                    'user_id' => $this->__id($user_id),
                    'refresh' => $this->default_token_refresh,
                    'expires' => $this->default_token_expiration,
                    'persist' => filter_var($stay_logged_in, FILTER_VALIDATE_BOOLEAN)
                ]],
                ['upsert' => true]
            );
        } catch (\Exception $e) {
            throw new \Exceptions\HTTP\Error("Failed to create session");
        }
        if ($result->getUpsertedCount() === 0 && $result->getModifiedCount() === 0) throw new \Exceptions\HTTP\BadRequest("You're already logged in.");
        return true;
    }

    function send_session_cookie($token) {
        $this->token_value = $token;
        setcookie($this->cookie_name, $token, $this->cookie_options);
        $_COOKIE[$this->cookie_name] = $token;
    }

    function is_expired() {
        if (!isset($this->session['expires'])) return true;
        if ($this->session['expires'] + $this->month <= $this->now) return true;
        return false;
    }

    function is_needing_refresh() {
        if (!isset($this->session['refresh'])) return true;
        if ($this->session['refresh'] + $this->day <= $this->now) return true;
        return false;
    }

    function send_session_delete() {
        if (!$this->context) return false;

        /** Send the header to expire the current cookie */
        setcookie($this->cookie_name, null, $this->now - $this->day, "/");
    }

    function logout_session() {
        $result = $this->updateOne(
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
