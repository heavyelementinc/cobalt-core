<?php

namespace Cobalt\Requests\Remote;

class Upgrade extends API {

    public function getIfaceName(): string {
        return "\\Cobalt\\Requests\\Tokens\\Upgrade";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function testAPI(): bool {
        return true;
    }

    public static function getMetadata(): array {
        return [
            'icon' => "<i name='git'></i>",
            'name' => "Upgrade Token",
            'view' => "/admin/api/upgrade.html"
        ];
    }
}
