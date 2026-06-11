<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Classes\ValidationResults\MergeResult;
use MongoDB\BSON\UTCDateTime;
use Validation\Exceptions\ValidationIssue;

class PasswordHashType extends MixedType {
    function field(string $class = "", array $misc = [], ?string $tag = null): string
    {
        $misc['value'] = "";
        return parent::field($class, $misc, "input-password" ?? $tag);
    }

    function filter($value) {
        $password_fail = "";

        /** Check if the password starts or ends with whitespace (not allowed) */
        if ($value !== trim($value)) {
            $password_fail .= "Passwords must not begin or end with spaces.\n";
        }

        /** Check if the password length meets the minimum required length */
        if (strlen($value) < app("Auth_min_password_length")) {
            $password_fail .= sprintf("Password must be at least %d characters long.\n",app("Auth_min_password_length"));
        }

        /** Detect if submitted passwords are all alphabetical or all numerical characters */
        if (ctype_alpha($value) || ctype_digit($value)) {
            $password_fail .= "Password must include at least one letter and one number.\n";
        }

        /** Check if strings are only comprised of alphanumeric characters */
        if (ctype_alnum($value)) {
            $password_fail .= "Password must contain at least one special character.\n";
        }

        if (!empty($password_fail)) {
            throw new ValidationIssue($password_fail);
        }

        $this->__validatedFields["flags.password_reset_required"] = false;
        $this->__validatedFields["flags.password_last_changed_by"] = session("_id") ?? "CLI";
        $this->__validatedFields["flags.password_last_changed_on"] = new UTCDateTime();

        /** Finally, we have a valid password. */
        return password_hash($value, PASSWORD_DEFAULT);
    }
}