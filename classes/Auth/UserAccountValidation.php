<?php
namespace Auth;
class UserAccountValidation{
    function __construct(){
        $this->collection = \db_cursor('users');
    }

    /** Functions are called via the \Auth\CRUDUser class with the following arguments: [$value, $field, $submitted_user_info] */
    function validate_fname($value){
        /** Let's *not* allow the user to have whitespace prefixing of suffixing their name. */
        return trim($value);
    }

    function validate_lname($value){
        /** Let's *not* allow the user to have whitespace prefixing of suffixing their name. */
        return trim($value);
    }

    /** Validate the username */
    function validate_uname($value){
        $v = trim($value);
        if($v !== $value) throw new \Exceptions\HTTP\BadRequest("Your username cannot begin or end with spaces.");
        /** Count the number of users with the supplied username */
        $uname_uniqueness = $this->collection->count(['uname' => $v]);
        /** If the username is not zero (meaning it's in use), throw an error */
        if($uname_uniqueness !== 0) throw new \Exceptions\HTTP\BadRequest("That username is already in use.");
        return $v;
    }

    function validate_pword($value){
        /** Check if the password starts or ends with whitespace (not allowed) */
        if( $value !== trim($value) ) throw new \Exceptions\HTTP\BadRequest("Passwords must not begin or end with spaces.");
        
        /** Check if the password length meets the minimum required length */
        if( strlen($value) < app("Auth_min_password_length") ) throw new \Exception\HTTP\BadRequest("Password must be at least " . app("Auth_min_password_length") . " characters long.");
        
        /** Detect if submitted passwords are all alphabetical or all numerical characters */
        if( ctype_alpha($value) || ctype_digit($value) ) throw new \Exception\HTTP\BadRequest("Password must include at least one letter and one number.");
        
        /** Check if strings are only comprised of alphanumeric characters */
        if( ctype_alnum($value) ) throw new \Exception\HTTP\BadRequest("Password must contain at least one special character.");
        
        /** Finally, we have a valid password. */
        return password_hash($value,PASSWORD_DEFAULT);
    }

    function validate_email($value){
        $value = trim($value);
        if(!filter_var($value,FILTER_VALIDATE_EMAIL)) throw new \Exceptions\HTTP\BadRequest("Email address contains invalid characters.");
        
        /** Count the number of users with the supplied email address */
        $email_uniqueness = $this->collection->count(['email' => $value]);
        
        /** If the email address is in use (meaning it's not zero), then throw an error */
        if($email_uniqueness !== 0) throw new \Exception\HTTP\BadRequest("That email address is already in use.");
        return $value;
    }

    function validate_prefs($value){
        /** TODO: Validate user preferences */
        return $value;
    }

    function validate_since($value){
        /** If the username hasn't been supplied, set it to null */
        if(!$value) $value = null;
        
        /** If it has been supplied, it will be in 'MM/DD/YYYY' format, convert it to a unix timestamp and convert to milliseconds */
        else $value = strtotime($value) * 1000;
        
        /** Return a Mongo UTC Date Time object */
        return new MongoDB\BSON\UTCDateTime($value);
    }

    function validate_verified($value){
        /**  */
        if(!is_bool($value)) throw new \Exceptions\HTTP\BadRequest("Validated must be a boolean value.");
        return $value;
    }

    function validate_groups($value){
        /** Get our groups */
        $perms = $GLOBALS['auth']->permissions;
        
        /** Check if we've been handed a group name */
        if(is_string($value)) {
            /** If it doesn't exist, return an array (we're assuming we're going to insert the validated results of this function
             * right into our database, so we return an empty array) */
            if(in_array($value,$perms->groups)) return [];
            return [$value]; // Return the value we've been handed as an array
        }
        
        $groups = [];
        foreach($value as $group){
            /** Check if the group is a valid group */
            if(in_array($value,$perms->groups)) array_push($groups,$group);
        }
        
        /** If the current app does not allow root group membership, let's remove the root group if it is detected */
        if( in_array("root",$groups) && !app("Auth_enable_root_group") ) unset($groups[array_search("root",$groups)]);
        
        return $groups; // Can be empty
    }

    function validate_permissions($value){
        /** Get our permissions */
        $perms = $GLOBALS['auth']->permissions;
        $mutant = [];
        
        foreach( $value as $permission => $value ){
            /** If the permission does not exist, we will skip this one */
            if(!key_exists($permission,$perms->valid)) continue;
        
            /** If it's not a boolean, we will throw an error */
            if(!is_bool($value)) throw new \Exceptions\HTTP\BadRequest("Could not validate user permission table");
        
            /** If we're here, we know it's safe to add the permission to the list */
            $mutant[$permission] = $value;
        }
        
        return $mutant; // Can be empty!
    }
}