<?php

namespace Cobalt\Integrations\Ghost;

use Cobalt\Integrations\Config;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Drivers\Database;
use TypeError;

class GhostConfig extends Config {
    const MODE_CONTENT = 0;
    const MODE_ADMIN = 1;
    private int $mode = self::MODE_CONTENT;

    public function fields(): array {
        return [
            // '__auth_mode' => [
                
            //     'default' => self::AUTH_BEARER
            // ],
            'content_api_key' => new StringResult,
            'admin_api_key' => new StringResult,
            'api_url' => new StringResult
        ];
    }

    public function getToken(): string {
        switch($this->mode) {
            case self::MODE_CONTENT:
                return (string)$this->content_api_key;
            case self::MODE_ADMIN:
                return $this->getAdminApiKey();
        }
        return (string)$this->content_api_key;
    }

    private function getAdminApiKey() {
        $exploded = explode(":",(string)$this->admin_api_key);
        $tk = [
            'kid' => $exploded[0]
            // [
            //     'id' => $exploded[0],
            //     'secret' => $exploded[1],
            // ]
        ];
        return createJWT($tk, [
            'exp' => strtotime("+4 min"),
            'iat' => time(),
            'aud' => "/admin"

        ], \hex2bin($exploded[1]));
    }

    public function getParam(): string {
        return (string)$this->content_api_key;
    }

    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }
    public function setMode(int $mode) {
        if($mode !== self::MODE_CONTENT && $mode !== self::MODE_ADMIN) {
            throw new TypeError("invalid mode");
        }
        $this->mode = $mode;
    }
}