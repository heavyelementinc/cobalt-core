<?php

namespace Cobalt\Integrations\Final\Discord;

use Cobalt\Integrations\Config;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;

class DiscordConfig extends Config {

    public function __set_manager(?Database $manager = null): ?Database {
        // return new Facebook();
        return null;
    }

    public function fields(): array {
        return [
            "application_id"  => new StringResult,
            "public_key"      => new StringResult,
            "bot_private_key" => new StringResult,
        ];
    }

    public function getToken(): string {
        return $this->application_id;
    }

    public function getParam(): string {
        return "";
    }

}