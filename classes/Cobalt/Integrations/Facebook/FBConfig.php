<?php

namespace Cobalt\Integrations\Facebook;

use Cobalt\Integrations\Config;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;

class FBConfig extends Config {

    public function fields(): array {
        return [
            "client_id" => new StringResult,
            "client_secret" => new StringResult,
            "auth_uri" => new StringResult,
            "token_uri" => new StringResult,
            "redirect_uris" => new ArrayResult,
            "scope" => [
                new ArrayResult,
                'delimiter' => ","
            ],
        ];
    }

    public function getToken(): string {
        return "";
    }

    public function getParam(): string {
        return "";
    }

}