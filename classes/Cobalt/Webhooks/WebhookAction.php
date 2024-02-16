<?php

namespace Cobalt\Webhooks;

use Validation\Normalize;

class WebhookAction extends Normalize {

    public function __get_schema(): array {
        return [
            'action_type' => [
                'valid' => [
                    'create'  => "Create Database Record",
                    'read'    => "Read Database Record",
                    'update'  => 'Update Database Record',
                    'destroy' => 'Destroy Database Record',
                    'request' => 'Send Request',
                ]
            ],
        ];
    }

}
