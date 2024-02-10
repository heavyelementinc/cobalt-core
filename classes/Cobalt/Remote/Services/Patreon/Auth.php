<?php

namespace Cobalt\Remote\Services\Patreon;

use Cobalt\Remote\Authenticator;

class Auth extends Authenticator {

    public function getNamespace(): string {
        return __NAMESPACE__;
    }

    public static function getMetadata():array {
        return json_decode(file_get_contents(__DIR__ . "/service.json"),true);
    }

    public function initializeCallback(&$metadata):void { }

    public function credentialsCallback(&$result):void { }

    public function storageCallback(&$toStore):void { }

    public function readCallback(&$result):void { }

}