<?php

namespace Cobalt\Integrations\Final\Patreon;

use Cobalt\Integrations\Config;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;

class SteamConfig extends Config {
    public function fields(): array {
        return [
            'key' => new StringResult,
            'domain_name' => new StringResult,
        ];
    }

    public function getToken(): string {
        return $this->key;
    }

    public function getParam(): string {
        return "";
    }

    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }
}