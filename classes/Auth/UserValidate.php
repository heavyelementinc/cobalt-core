<?php

namespace Auth;

use \Validation\Exceptions\ValidationIssue;

class UserValidate extends \Validation\Validate {

    function __construct() {
        $this->collection = \db_cursor('users');
    }

    protected function __get_schema() {
        return [
            "fname" => [],
            "lname" => [],
            "uname" => [],
            "pword" => [],
            "email" => [],
            // "prefs" => [],
            // "since" => [],
            // "verified" => [],
            // "groups" => [],
            // "permissions" => [],
        ];
    }

    /** Functions are called via the \Auth\CRUDUser class with the following arguments: [$value, $field, $submitted_user_info] */
    function fname($value) {
        /** Let's *not* allow the user to have whitespace prefixing of suffixing their name. */
        return trim($value);
    }

    function lname($value) {
        /** Let's *not* allow the user to have whitespace prefixing of suffixing their name. */
        return trim($value);
    }

    /** Validate the username */
    function uname($value) {
        $v = trim($value);
        if ($v !== $value) throw new ValidationIssue("Your username cannot begin or end with spaces.");
        /** Count the number of users with the supplied username */
        $uname_uniqueness = $this->collection->count(['uname' => $v]);
        /** If the username is not zero (meaning it's in use), throw an error */
        if ($uname_uniqueness !== 0) throw new ValidationIssue("That username is already in use.");
        return $v;
    }

    function pword($value) {
        $password_fail = "";

        /** Check if the password starts or ends with whitespace (not allowed) */
        if ($value !== trim($value)) $password_fail .= "Passwords must not begin or end with spaces.\n";

        /** Check if the password length meets the minimum required length */
        if (strlen($value) < app("Auth_min_password_length")) $password_fail .= "Password must be at least " . app("Auth_min_password_length") . " characters long.\n";

        /** Detect if submitted passwords are all alphabetical or all numerical characters */
        if (ctype_alpha($value) || ctype_digit($value)) $password_fail .= "Password must include at least one letter and one number.\n";

        /** Check if strings are only comprised of alphanumeric characters */
        if (ctype_alnum($value)) $password_fail .= "Password must contain at least one special character.\n";

        if (!empty($password_fail)) throw new ValidationIssue($password_fail);

        /** Finally, we have a valid password. */
        return password_hash($value, PASSWORD_DEFAULT);
    }

    function email($value) {
        $value = trim($value);
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) throw new ValidationIssue("Email address contains invalid characters.");

        /** Count the number of users with the supplied email address */
        $email_uniqueness = $this->collection->count(['email' => $value]);

        /** If the email address is in use (meaning it's not zero), then throw an error */
        if ($email_uniqueness !== 0) throw new ValidationIssue("That email address is already in use.");
        return $value;
    }

    function prefs($value) {
        /** TODO: Validate user preferences */
        return $value;
    }

    function since($value = null) {
        /** If the username hasn't been supplied, set it to null */
        if (!$value) $value = null;

        /** If it has been supplied, it will be in 'MM/DD/YYYY' format, convert it to a unix timestamp and convert to milliseconds */
        else $value = strtotime($value) * 1000;

        /** Return a Mongo UTC Date Time object */
        return $this->make_date($value);
    }

    function verified($value) {
        /**  */
        if (!is_bool($value)) throw new ValidationIssue("Validated must be a boolean value.");
        return $value;
    }

    function groups($value) {
        /** Get our groups */
        $perms = $GLOBALS['auth']->permissions;

        /** Check if we've been handed a group name */
        if (is_string($value)) {
            /** If it doesn't exist, return an array (we're assuming we're going to insert the validated results of this function
             * right into our database, so we return an empty array) */
            if (in_array($value, $perms->groups)) return [];
            return [$value]; // Return the value we've been handed as an array
        }

        $groups = [];
        foreach ($value as $group) {
            /** Check if the group is a valid group */
            if (in_array($value, $perms->groups)) array_push($groups, $group);
        }

        /** If the current app does not allow root group membership, let's remove the root group if it is detected */
        if (in_array("root", $groups) && !app("Auth_enable_root_group")) unset($groups[array_search("root", $groups)]);

        return $groups; // Can be empty
    }

    function permissions($value) {
        /** Get our permissions */
        $perms = $GLOBALS['auth']->permissions;
        $mutant = [];

        foreach ($value as $permission => $value) {
            /** If the permission does not exist, we will skip this one */
            if (!key_exists($permission, $perms->valid)) continue;

            /** If it's not a boolean, we will throw an error */
            if (!is_bool($value)) throw new ValidationIssue("Could not validate user permission table");

            /** If we're here, we know it's safe to add the permission to the list */
            $mutant[$permission] = $value;
        }

        return $mutant; // Can be empty!
    }
}
