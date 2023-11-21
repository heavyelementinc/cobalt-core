<?php

namespace Contact;

use Cobalt\PersistanceMap;
use Cobalt\SchemaPrototypes\ArrayResult;
use Cobalt\SchemaPrototypes\DateResult;
use Cobalt\SchemaPrototypes\EmailAddressResult;
use Cobalt\SchemaPrototypes\EnumResult;
use Cobalt\SchemaPrototypes\IdResult;
use Cobalt\SchemaPrototypes\IpResult;
use Cobalt\SchemaPrototypes\MarkdownResult;
use Cobalt\SchemaPrototypes\PhoneNumberResult;
use Cobalt\SchemaPrototypes\StringResult;
use Cobalt\SchemaPrototypes\UserIdArrayResult;
use Cobalt\SchemaPrototypes\UserIdResult;

class Persistance extends PersistanceMap {

    public function __get_schema(): array {
        $addtl = new AdditionalContactFields();
        $fields = $addtl->__get_schema();
        $schema = [
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
                new UserIdArrayResult,
                'getUsers' => function ($val, $ref) {
                    if(!has_permission('Contact_form_submissions_modify', null, null, false)) return "";
                    return $ref->eachToView("{{doc.uname}}");
                },
                'status' => function ($val, $ref) {
                    if($val) return "read";
                    return "unread";
                }
            ],
            "date" => new DateResult,
            "ip" => new IpResult,
        ];
        $schema += $fields;
        return $schema;
    }

}