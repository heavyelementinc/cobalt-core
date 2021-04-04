<?php
/** This class handles authenticating the current request.
 * 
 * 
 */
namespace Auth;
class Authentication{
    function __construct(){
        if(app("Auth_user_accounts_enabled")) return false;
        $this->session = new CurrentSession();
        if(isset($this->session->session->user_id)) $ua = new UserAccount($this->session->session->user_id);
        else return $this;
        $this->user = $ua->get_user_by_id($this->session->session->user_id);
        $this->permissions = new Permissions();
        if(!$this->user) {
            $GLOBALS['session'] = null;
            return $this;
        }
        
        $GLOBALS['session'] = (array)$this->user;
        $GLOBALS['session']['session_data'] = (array)$this->session;
    }

    /** Our user login routine. */
    function login_user($username, $password, $stay_logged_in = false){
        $stock_message = "Invalid credentials.";
        /** Get our user by their username or email address */
        $ua = new UserAccount();
        $user = $ua->get_user_by_uname_or_email($username);
        
        /** If we don't have a user account after our query has run, then the client
         * submitted a username/email address that hasn't been registered, yet. */
        if($user === null) throw new \Exceptions\HTTP\Unauthorized($stock_message);
        
        /** Check if our password verification has failed and throw an error if it did */
        if(!\password_verify($password,$user['pword'])) throw new \Exceptions\HTTP\Unauthorized($stock_message);

        /** Update the user's session information */
        $result = $this->session->login_session($user['_id'],$stay_logged_in);
        
        /** If the session couldn't be updated, we throw an error */
        if(!$result) throw new \Exception\HTTP\BadRequest("An unknown error occured.");
        
        return [
            'login' => 'successful'
        ];
    }

    /** Our user logout routine */
    function logout_user(){
        // $this->send_session_delete();
        return $this->session->logout_session();
    }

    /** Our permission check routine */
    function has_permission($permission,$group = null){
        // If the user is not logged in, they obviously don't have permission
        if(!$this->user) throw new \Exceptions\HTTP\Unauthorized("You're not logged in.",['login' => true]);
        // If the permission is not valid, we throw an exception.
        if(!isset($this->permissions->valid[$permission])) throw new \Exception("The \"$permission\" permission cannot be validated!");
        
        // If the app allows root users AND the user belongs to the root group, they have permission
        if(app('Auth_enable_root_group') && in_array('root',(array)$this->user['groups'])) return true;

        // If the user account has the permission, we return that value--whatever it may be
        if(isset($this->user['permissions'][$permission])) return $this->user['permissions'][$permission];
 
        // If no group is specified, return the permission's default value
        if($group === null) return $this->permissions->valid[$permission]['default'];

        // Check if the user DOES NOT have the group in their groups
        if(!in_array($group,(array)$this->user['groups'])) return false;

        // Check if this permission belongs to the group specified
        if(isset($this->permissions->valid[$permission]['group'][$group])) return $this->permissions->valid[$permissions]['groups'][$group];
        
        // Return false? (Shouldn't this be returning TRUE?)
        return false;
    }

    
}