<?php

namespace Cobalt\DataModel\Types;

use Cobalt\DataModel\Models\PasswordModel;
use DateTime;
use Override;

class PasswordHashType extends StringType {
    #[Override]
    public function filter(mixed $toValidate, mixed $raw): mixed {
        
        /** Check if the password starts or ends with whitespace (not allowed) */
        if($toValidate !== trim($toValidate)) {
            $this->filterResult->addIssue($this,"Passwords may not begin or end with spaces.");
        }

        /** Check if the password length meets the minimum required length */
        if(strlen($toValidate) < __APP_SETTINGS__['Auth_min_password_length']) {
            $this->filterResult->addIssue($this, sprintf("Password must be at least %d characters long.\n",app("Auth_min_password_length")));
        }

        /** Detect if submitted passwords are all alphabetical or all numerical characters */
        if (ctype_alpha($toValidate) || ctype_digit($toValidate)) {
            $this->filterResult->addIssue($this, "Password must include at least one letter and one number.");
        }
        /** Check if strings are only comprised of alphanumeric characters */
        if (ctype_alnum($toValidate)) {
            $this->filterResult->addIssue($this, "Password must contain at least one special character.\n");
        }

        if($this->model instanceof PasswordModel) {
            // Signal that we've updated our value
            $this->model->lastUpdated->updateValue(new DateTime());
            $this->passwordResetRequired->updateValue(false);
        }

        return password_hash($toValidate, PASSWORD_DEFAULT);
    }

    public function passwordVerify(string $password):bool {
        return password_verify($password, $this->hash->value);
    }
}