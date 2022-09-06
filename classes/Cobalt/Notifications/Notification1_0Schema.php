<?php

namespace Cobalt\Notifications;

class Notification1_0Schema extends \Validation\Normalize {
    function __get_schema(): array {
        return [
            'subject' => [
                'max_char_length' => 80
            ],
            'body' => [],
            'sent' => [],
            'type' => [],
            'for' => new Notification1_0Subdocument(),

            // We need a way to make notifications actionable
            'action' => [
                'set' => null
            ],
            'action.params' => [
                'type' => 'array'
            ],
            'action.context' => [],
            'action.route' => [
                'set' => function ($val) {
                    validate_route($val, $this->{'action.context'});
                }
            ],
            'action.path' => []
        ];
    }

    function getTemplate() {
        return "/cobalt/notifications/notification-1.0.html";
    }
}

/* 'for.$.user' => [
    'set' => function ($value) {
        return new \MongoDB\BSON\ObjectId($value);
    }
],
'for.$.read' => [
    'set' => fn ($val) => $this->boolhelper($val),
    'default' => false
],
'for.$.recieved' => [
    'get' => fn ($val) => $this->get_date($val, 'verbose'),
    'set' => fn ($val) => $this->make_date($val)
],
