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
            'icon' => "<ion-icon name='logo-stripe'></ion-icon>",
            'name' => "Stripe"
        ];
    }
}