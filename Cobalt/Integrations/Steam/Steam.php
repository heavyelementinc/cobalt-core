<?php

namespace Cobalt\Integrations\Final\Patreon;

use Cobalt\Integrations\Base;
use Cobalt\Integrations\Config;
use Cobalt\Integrations\OauthBase;

class Steam extends Base {
    public function publicName(): string {
        return "Steam";
    }

    public function publicIcon(): string {
        return "steam";
    }

    public function get_unique_token(): string {
        return "steam_key";
    }

    public function configuration(): Config {
        return new SteamConfig();
    }

    public function status(): int {
        return 0;
    }

    public function html_token_editor(): string {
        return "Cobalt/Integrations/Final/Steam/templates/steam-token.php";
    }

}