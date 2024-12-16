<?php

namespace Cobalt\Integrations\Facebook;

use Cobalt\Integrations\Config;
use Cobalt\Integrations\OauthBase;

class Facebook extends OauthBase {

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
        return "Facebook";
    }

    public function publicIcon(): string {
        return "facebook";
    }

    public function get_unique_token(): string {
        return "facebook";
    }

    public function configuration(): Config {
        return new FBConfig();
    }

    public function html_token_editor(): string {
        return view("/admin/integrations/edit/facebook.html");
    }

}