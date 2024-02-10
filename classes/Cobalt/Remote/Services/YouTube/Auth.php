<?php

namespace Cobalt\Remote\Services\YouTube;

use Cobalt\Remote\Authenticator;

class Auth extends Authenticator {

    public function initializeCallback(&$metadata): void { }

    public function credentialsCallback(&$result): void { }

    public function storageCallback(&$toStore): void { }

    public function readCallback(&$result): void { }

    public function getNamespace(): string {
        return __NAMESPACE__;
    }

    public static function getMetadata(): array {
        return get_json(__DIR__ . "/service.json");
    }

}