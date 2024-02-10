<?php

namespace Cobalt\Remote\Services\Patreon;

use Cobalt\Remote\AuthSchema;
use Cobalt\SchemaPrototypes\Basic\StringResult;

class Schema extends AuthSchema {
    function auth_fields(&$fields) {
        $fields = [
            'client_id' => [
                new StringResult,
                'label' => 'Patreon Client ID <small>To find your campaign ID, navigate to your campaign page and use this function in the console javascript:prompt(\'Campaign ID\',window.patreon.bootstrap.creator.data.id);</small>'
            ],
            'client_secret' => [
                new StringResult,
                'label' => 'Client Secret',
            ],
            'access_token' => [
                new StringResult,
                'label' => "Creator's Access Token",
            ],
            'refresh_token' => [
                new StringResult,
                'label' => "Creator's Refresh Token"
            ]
        ];
    }
}