<?php

/** This class handles authenticating the current request.
 * 
 * 
 */

namespace Auth;

class Authentication {
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

        $GLOBALS['session'] = new UserSchema($this->user);
        $GLOBALS['session']['session_data'] = (array)$this->session;
    }

    /** Our user login routine. */
    function login_user($username, $password, $stay_logged_in = false) {
        $stock_message = "Invalid credentials.";
        /** Get our user by their username or email address */
        $ua = new UserCRUD();
        $user = $ua->getUserByUnameOrEmail($username);

        /** If we don't have a user account after our query has run, then the client
         * submitted a username/email address that hasn't been registered, yet. */
        if ($user === null) throw new \Exceptions\HTTP\Unauthorized($stock_message);

        /** Check if our password verification has failed and throw an error if it did */
        if (!\password_verify($password, $user['pword'])) throw new \Exceptions\HTTP\Unauthorized($stock_message);

        /** Update the user's session information */
        $result = $this->session->login_session($user['_id'], $stay_logged_in);

        /** If the session couldn't be updated, we throw an error */
        if (!$result) throw new \Exceptions\HTTP\BadRequest("An unknown error occured.");

        return [
            'login' => 'successful'
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
        if (!$user) throw new \Exceptions\HTTP\Unauthorized("You're not logged in.", ['login' => true]);

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
        if (($user->flags['password_reset_required'] ?? false) === true && $user === $this->user) throw new \Exceptions\HTTP\PasswordUpdateRequired("You must update your password.");

        // app('Auth_require_verified_status') && 

        // If the user account stores the permission, we return that value, 
        // whatever it may be
        if (isset($user->permissions->{$permission})) return $user->permissions->{$permission};

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
}
