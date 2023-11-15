<?php

namespace Contact;

use Cobalt\Schema;
use Cobalt\SchemaPrototypes\BooleanResult;
use Cobalt\SchemaPrototypes\DateResult;
use Cobalt\SchemaPrototypes\EmailAddressResult;
use Cobalt\SchemaPrototypes\EnumResult;
use Cobalt\SchemaPrototypes\IpResult;
use Cobalt\SchemaPrototypes\MarkdownResult;
use Cobalt\SchemaPrototypes\PhoneNumberResult;
use Cobalt\SchemaPrototypes\StringResult;

class Persistance extends Schema {

    public function __get_schema(): array {
        return [
            "name" => [
                new StringResult,
                'char_limit' => 150,
            ],
            "organization" => [
                new StringResult,
                'char_limit' => 150,
                'illegal_chars' => '<>'
            ],
            "email" => new EmailAddressResult,
            "phone" => new PhoneNumberResult,
            "preferred" => [
                new EnumResult,
                'valid' => [
                    'email' => "Email",
                    'phone' => "Phone"
                ]
            ],
            "additional" => [
                new MarkdownResult,
                'char_limit' => 1800
            ],
            "read" => [
                new BooleanResult,
                'validate' => function ($val, $ref) {
                    return $ref->isBoolean($val);
                },
                'status' => function ($val, $ref) {
                    if($val) return "read";
                    return "unread";
                }
            ],
            "date" => new DateResult,
            "ip" => new IpResult,
        ];
    }

}