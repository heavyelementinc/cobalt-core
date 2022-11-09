<?php

namespace Cobalt\Requests\Remote;

class Stripe extends API {

    public function getIfaceName(): string {
        return "\\Cobalt\\Requests\\Tokens\\Stripe";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function refreshTokenCallback($result): string {
        return "";
    }

    public static function getMetadata(): array {
        return [
            'icon' => "<i name='credit-card-settings-outline'></i>",
            'name' => "Stripe"
        ];
    }

    public function testAPI(): bool {
        return true;
    }
}
