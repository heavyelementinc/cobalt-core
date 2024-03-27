<?php

namespace Cobalt\Integrations\YouTube;

use Auth\UserCRUD;
use Cobalt\Integrations\OauthBase;
use Cobalt\Integrations\Config;
use Cobalt\Integrations\YouTube\Config as YouTubeConfig;
use DateTime;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\Unauthorized;

class YouTube extends OauthBase {


    public function publicName(): string {
        return "YouTube OAuth";
    }

    public function publicIcon(): string {
        return "youtube";
    }

    function get_unique_token(): string {
        return "YouTubeToken";
    }

    public function configuration(): Config {
        return new YouTubeConfig();
    }

    public function html_token_editor(): string {
        return view("/admin/integrations/edit/youtube-api.html");
    }

    function oauth_errors():array {
        return [
            'access_denied' => [
                'callback' => fn ($err_code) => false,
                'message' => fn ($err_code) => "Access Denied"
            ]
        ];
    }

}