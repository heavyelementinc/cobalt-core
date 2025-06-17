<?php

namespace Cobalt\Integrations\GoogleOauth;

use Auth\UserCRUD;
use Cobalt\Integrations\Config;
use Cobalt\Integrations\OauthBase;
use DateInterval;
use DateTime;
use Exception;

abstract class GoogleOauth extends OauthBase {
    public function oauth_errors(): array {
        return [];
    }
}