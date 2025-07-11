<?php

namespace Cobalt\Integrations\Final\MailChimp;

use Cobalt\Integrations\Config as IntegrationsConfig;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;

class MailChimpConfig extends IntegrationsConfig {

    public function __set_manager(?Database $manager = null): ?Database {
        return null;
        // return new MailChimp();
    }
    public function getToken(): string {
        return $this->api_key->getValue();
    }

    public function getParam(): string {
        return "cobaltengine";
    }

    public function fields(): array {
        return [
            "region" => new StringResult,
            "api_key" => new StringResult,
            '__auth_mode' => [
                new EnumResult,
                'valid' => [
                    self::AUTH_BASIC => "Basic Authentication",
                ],
                'default' => self::AUTH_BASIC
            ],
            '__requestEncoding' => [
                new EnumResult,
                'valid' => [
                    REQUEST_ENCODE_JSON => 'JSON'
                ],
                'default' => REQUEST_ENCODE_JSON
            ]
        ];
    }
    
}