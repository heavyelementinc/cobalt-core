<?php

namespace Contact;

use Cobalt\Schema;
use Cobalt\SchemaPrototypes\BooleanResult;
use Cobalt\SchemaPrototypes\DateResult;
use Cobalt\SchemaPrototypes\EmailAddressResult;
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
                "type" => new StringResult,
                'valid' => [
                    'email' => "Email",
                    'phone' => "Phone"
                ]
            ],
            "additional" => new StringResult,
            "read" => new BooleanResult,
            "date" =>  new DateResult,
            "ip" => new StringResult,
        ];
    }

}