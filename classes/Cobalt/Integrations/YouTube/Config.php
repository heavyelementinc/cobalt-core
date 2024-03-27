<?php

namespace Cobalt\Integrations\YouTube;

use Cobalt\Integrations\Config as IntegrationsConfig;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;

class Config extends IntegrationsConfig {

    public function getToken(): string {
        $tokens = session("integrations.YouTube");
        $token = $tokens[count($tokens) - 1];
        // If token is expired and we have a refresh token, fetch a new one first
        // if( EXPIRED && $token['expiration] < NOW) FETCH NEW ONE
        return $token['details']['access_token'];
    }

    public function getParam(): string {
        return "";
    }

    public function fields(): array {
        return [
            "client_id" => new StringResult,
            "project_id" => new StringResult,
            "auth_uri" => new StringResult,
            "token_uri" => new StringResult,
            "auth_provider_x509_cert_url" => new StringResult,
            "client_secret" => new StringResult,
            "redirect_uris" => [
                new ArrayResult,
            ],
            "scope" => new ArrayResult,
            "access_type" => [
                new EnumResult,
                'valid' => [
                    'online' => 'Online',
                    'offline' => 'Offline',
                ]
            ]
        ];
    }
    
}