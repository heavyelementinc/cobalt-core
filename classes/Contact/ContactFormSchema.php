<?php

namespace Contact;

use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\UTCDateTime;
use Validation\Normalize;

class ContactFormSchema extends Normalize {

    function __get_schema(): array {
        return [
            "name" => [],
            "organization" => [],
            "email" => [
                "set" => fn ($val) => $this->validate_email($val)
            ],
            "phone" => [
                "set" => fn ($val) => $this->validate_phone($val)
            ],
            "preferred" => [
                'valid' => [
                    'email' => "Email",
                    'phone' => "Phone"
                ]
            ],
            "additional" => [],
            "read" => [
                "set" => fn ($val) => throw new Unauthorized("This cannot be modified")
            ],
            "date" => [
                "set" => fn() => new UTCDateTime(),
                "display" => fn($val) => $this->get_date($val, "verbose")
            ],
            "ip" => [
                "set" => fn() => ""
            ],
            "read_status" => [
                "get" => function () {
                    $id = session("_id");
                    if(!key_exists("read", $this->__dataset)) return "unread";
                    // if(!key_exists("read",$this->__dataset)) return "unread";
                    $array = $this->__dataset['read'];
                    return in_array($id, $array);
                },
                "display" => fn () => ($this->read_status) ? "read" : "unread"
            ],
            "read_status_inverted" => [
                "get" => fn () => !$this->read_status,
                "display" => fn () => ($this->read_status_inverted) ? "read" : "unread"
            ]
        ];
    }

}
