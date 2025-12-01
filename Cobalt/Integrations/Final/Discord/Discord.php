<?php

namespace Cobalt\Integrations\Final\Discord;

use Cobalt\Integrations\Config;
use Cobalt\Integrations\OauthBase;

class Discord extends OauthBase {
    public function get_oauth_credentials(): array {
        return [];
    }

    public function status(): int {
        return self::STATUS_CHECK_OK;
    }

    public function oauth_errors(): array {
        return [
            'user_denied' => [
                'callback' => fn () => false,
                'message' => fn () => "You denied the request"
            ]
        ];
    }

    public function publicName(): string {
        return "Discord";
    }

    public function publicIcon(): string {
        return "discord";
    }

    public function get_unique_token(): string {
        return "discord";
    }

    public function configuration(): Config {
        return new DiscordConfig();
    }

    public function html_token_editor(): string {
        return view("Cobalt/Integrations/Final/Discord/discord.html");
    }

}