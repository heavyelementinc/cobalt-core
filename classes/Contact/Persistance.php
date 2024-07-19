<?php

namespace Contact;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\EmailAddressResult;
use Cobalt\SchemaPrototypes\Compound\IpResult;
use Cobalt\SchemaPrototypes\Compound\MarkdownResult;
use Cobalt\SchemaPrototypes\Compound\PhoneNumberResult;
use Cobalt\SchemaPrototypes\Compound\UserIdArrayResult;

class Persistance extends PersistanceMap {

    public function __get_schema(): array {
        $addtl = new AdditionalContactFields();
        $this->__set_index_checkbox_state(true);
        $fields = $addtl->__get_schema();
        $schema = [
            "name" => [
                new StringResult,
                'char_limit' => 150,
                'index' => [
                    'title' => 'Name',
                    'order' => 0,
                    'sort' => -1,
                    'view' => fn ($val) => $val->getRaw()
                ]
            ],
            "organization" => [
                new StringResult,
                'char_limit' => 150,
                'illegal_chars' => '<>',
                'index' => [
                    'title' => 'Org',
                    'order' => 1
                ]
            ],
            "email" => [
                new EmailAddressResult,
                'index' => [
                    'title' => 'Email',
                    'order' => 2,
                ]
            ],
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
                },
                'index' => [
                    'title' => 'Read Status',
                    'order' => 3,
                    'sortable' => false,
                    'view' => function ($val) {
                        if(in_array(session("_id"), $val)) return "Read";
                        return "Unread";
                    }
                ]
            ],
            "date" => [
                new DateResult,
                'index' => [
                    'title' => 'Date',
                    'order' => 1,
                    'view' => fn ($val) => $val->format("c")
                ]
            ],
            "ip" => new IpResult,
        ];
        $schema += $fields;
        return $schema;
    }

}