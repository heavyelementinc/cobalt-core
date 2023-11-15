<?php

namespace Contact;

use Cobalt\Schema;
use Cobalt\SchemaPrototypes\BooleanResult;
use Cobalt\SchemaPrototypes\DateResult;
use Cobalt\SchemaPrototypes\EmailAddressResult;
use Cobalt\SchemaPrototypes\EnumResult;
use Cobalt\SchemaPrototypes\PhoneNumberResult;
use Cobalt\SchemaPrototypes\StringResult;

class Persistance extends Schema {

    public function __get_schema(): array {
        return [
            "name" => new StringResult,
            "organization" => new StringResult,
            "email" => new EmailAddressResult,
            "phone" => new PhoneNumberResult,
            "preferred" => [
                "type" => new EnumResult,
                'valid' => [
                    'email' => "Email",
                    'phone' => "Phone"
                ]
            ],
            "additional" => new StringResult,
            "read" => [
                'type' => new BooleanResult,
                'validate' => function ($val, $ref) {
                    return $ref->isBoolean($val);
                },
                'status' => function ($val, $ref) {
                    if($val) return "read";
                    return "unread";
                }
            ],
            "date" =>  new DateResult,
            "ip" => new StringResult,
        ];
    }

}