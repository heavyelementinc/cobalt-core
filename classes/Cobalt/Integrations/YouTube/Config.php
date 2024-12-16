<?php

namespace Cobalt\Integrations\YouTube;

use Auth\UserCRUD;
use Cobalt\Integrations\Config as IntegrationsConfig;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\EnumResult;
use Cobalt\SchemaPrototypes\Basic\NumberResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use DateTime;
use Drivers\Database;

class Config extends IntegrationsConfig {

    public function __set_manager(?Database $manager = null): ?Database {
        return null;//new YouTube();
    }

    public function getToken(): string {
        // /** @var BSONDocument */
        $tokens = session()->integrations->YouTube;
        $token = $tokens[count($tokens) - 1];
        // If token is expired and we have a refresh token, fetch a new one first
        // if( EXPIRED && $token['expiration] < NOW) FETCH NEW ONE
        // if(property_exists("expiration", $token) && $token?->details?->refresh_token) {
        //     /** @var DateTime */
        //     $date = $token->expiration->toDateTime();
        //     $now = new DateTime();
        //     if($date->getTimestamp() < $now->getTimestamp()) {
        //         $userCRUD = new UserCRUD();
        //         $user = $userCRUD->findOne(['integrations.YouTube.details.refresh_token' => $token['details']['refresh_token']]);
        //     }
        // }
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
                ],
                'default' => 'offline'
            ]
        ];
    }
    
}