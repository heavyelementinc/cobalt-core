<?php

namespace Cobalt\Integrations\Final\Patreon;

use Cobalt\Integrations\Config;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;

class PatreonConfig extends Config {
    public function fields(): array {
        return [
            'campaign_id'   => new StringResult,
            'client_id'     => new StringResult,
            'client_secret' => new StringResult,
            'access_token'  => new StringResult,
            'refresh_token' => new StringResult,
        ];
    }

    public function getToken(): string {
        return $this->access_token;
    }

    public function getParam(): string {
        return "";
    }

    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }

}