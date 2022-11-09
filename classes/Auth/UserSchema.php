<?php

/** UserSchema - The user account normalization routines
 * 
 * To specify additional user fields in your project, you can add the class
 * \Auth\AdditionalUserFields to your project and specify:
 * 
 * ```php
 * public function __get_additional_schema():array // In schema format
 * public function __get_additional_user_tab():string // Path to template
 * 
 * 
 */

namespace Auth;

use \Validation\Exceptions\ValidationIssue;
use \Auth\AdditionalUserFields;

class UserSchema extends \Validation\Normalize {

    function __construct($doc = null, $normalize_get = true) {
        $this->collection = \db_cursor('users');
        parent::__construct($doc, $normalize_get);
    }

    function __get_schema(): array {
        $integrate = [];
        $additional = null;
        if(file_exists(__APP_ROOT__ . "/classes/Auth/AdditionalUserFields")) $additional = new AdditionalUserFields();
        if ($additional) $integrate = $additional->__get_additional_schema();
        return array_merge([
            'fname' => [
                'display' => fn () => $this->name
            ],
            'lname' => [],
            'uname' => [],
            'name'  => [
                'get' => function () {
                    if($this->fname && $this->lname) return "<span title='Username: $this->uname'>$this->fname " . $this->lname[0] . ".</span>";
                    return $this->uname;
                },
                'set' => null
            ],
            'pword' => [],
            'email' => [],
            'flags.verified' => [
                'set' => 'boolean_helper',
                'groups' => ['flags'],
                'tag' => 'input-switch',
                'attributes' => [],
                'label' => 'Is user verified'

            ],
            'flags.password_reset_required' => [
                'set' => 'boolean_helper',
                'groups' => ['flags'],
                'tag' => 'input-switch',
                'attributes' => [],
                'label' => 'Require password reset on next login'
            ],
            // "prefs" => [],
            // "since" => [],
            // "verified" => [],
            // "groups" => [],
            // "permissions" => [],
        ], $integrate);
    }

    /** Functions are called via the \Auth\CRUDUser class with the following arguments: [$value, $field, $submitted_user_info] */
    function set_fname($value) {
        $this->required_field($value);
        /** Let's *not* allow the user to have whitespace prefixing of suffixing their name. */
        return trim($value);
    }

    function set_lname($value) {
        /** Let's *not* allow the user to have whitespace prefixing of suffixing their name. */
        return trim($value);
    }

    /** Validate the username */
    function set_uname($value) {
        $this->required_field($value);
        $v = trim($value);
        if ($v !== $value) throw new ValidationIssue("Your username cannot begin or end with spaces.");
        /** Count the number of users with the supplied username */
        $uname_uniqueness = $this->collection->count(['uname' => $v]);
        /** If the username is not zero (meaning it's in use), throw an error */
        if ($uname_uniqueness !== 0) throw new ValidationIssue("That username is already in use.");
        return $v;
    }

    function set_pword($value) {
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

        $this->__modify("flags.password_reset_required", false);
        $this->__modify("flags.password_last_changed_by", session("_id") ?? "CLI");
        $this->__modify("flags.password_last_changed_on", $this->make_date());

        /** Finally, we have a valid password. */
        return password_hash($value, PASSWORD_DEFAULT);
    }

    function set_email($value) {
        $value = trim($value);
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) throw new ValidationIssue("Email address contains invalid characters.");

        /** Count the number of users with the supplied email address */
        $email_uniqueness = $this->collection->count(['email' => $value]);

        /** If the email address is in use (meaning it's not zero), then throw an error */
        if ($email_uniqueness !== 0) throw new ValidationIssue("That email address is already in use.");
        return $value;
    }

    function set_prefs($value) {
        /** TODO: Validate user preferences */
        return $value;
    }

    function set_since($value = null) {
        /** If the username hasn't been supplied, set it to null */
        if (!$value) $value = null;

        /** If it has been supplied, it will be in 'MM/DD/YYYY' format, convert it to a unix timestamp and convert to milliseconds */
        else $value = strtotime($value) * 1000;

        /** Return a Mongo UTC Date Time object */
        return $this->make_date($value);
    }

    function set_verified($value) {
        /**  */
        if (!is_bool($value)) throw new ValidationIssue("Verified field must be a boolean value.");
        return $value;
    }

    function set_groups($value) {
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

    function set_permissions($value) {
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
