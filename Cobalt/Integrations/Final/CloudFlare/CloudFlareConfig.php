<?php

namespace Cobalt\Integrations\Final\CloudFlare;

use Cobalt\Integrations\Config as IntegrationsConfig;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;

class CloudFlareConfig extends IntegrationsConfig {

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
            "api_key" => new StringResult,
            "zoneId" => new StringResult,
            '__auth_mode' => [
                new EnumResult,
                'valid' => [
                    self::AUTH_BEARER => "Bearer Token",
                ],
                'default' => self::AUTH_BEARER
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