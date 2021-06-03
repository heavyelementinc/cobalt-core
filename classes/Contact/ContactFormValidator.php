<?php

namespace Contact;

use \Validation\Exceptions\ValidationIssue;

class ContactFormValidator extends \Validation\Validate {
    function __get_schema() {
        return [
            "name" => [],
            "organization" => [],
            "email" => [],
            "phone" => [],
            "preferred" => [],
            "additional" => [],
        ];
    }

    function name($value) {
        $this->required_field($value);
        return $this->sanitize($value);
    }

    function organization($value) {
        return $this->sanitize($value);
    }

    function email($value) {
        $this->one_required($value, 'email', "Either an email or phone number must be provided");
        return $this->validate_email($value);
    }

    function phone($value) {
        $this->one_required($value, 'phone', "Either a phone number or email must be provided");
        return $this->validate_phone($value);
    }

    function preferred($value) {
        if (!in_array($value, ['email', 'phone'])) throw new ValidationIssue("Invalid selection");
        if (empty($this->__to_validate[$value])) throw new ValidationIssue("The contact method you specified should be provided");
        return $value;
    }

    function additional($value) {
        return $this->sanitize($value);
    }
}
