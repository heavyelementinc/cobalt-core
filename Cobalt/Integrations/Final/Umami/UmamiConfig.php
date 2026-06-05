<?php

namespace Cobalt\Integrations\Final\Umami;

use Cobalt\Integrations\Config;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use DateTime;
use Override;
use Drivers\Database;

class UmamiConfig extends Config {
    #[Override]
    public function fields(): array {
        return [
            'instance_url' => new StringResult,
            'username' => new StringResult,
            'password' => new StringResult,
            'token' => new StringResult,
        ];
    }

    #[Override]
    public function getToken(): string {
        $date = new DateTime((string)$this->token->createdAt);
        $now = new DateTime();
        $interval = $now->diff($date);
        $interval->format("%s");
        // if()
        return (string)$this->token->token;
    }

    #[Override]
    public function getParam(): string {
        return (string)$this->token;
    }

    #[Override]
    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }

}