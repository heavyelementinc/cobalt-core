<?php

namespace Cobalt\Integrations\Final\Ghost;

use Cobalt\Integrations\Config;
use Cobalt\Integrations\Base;


class Ghost extends Base {
    public function publicName(): string {
        return "Ghost";
    }

    public function publicIcon(): string {
        return "ghost";
    }

    public function get_unique_token(): string {
        return "ghost";
    }

    public function configuration(): Config {
        return new GhostConfig();
    }

    public function html_token_editor(): string {
        return view("Cobalt/Integrations/Final/Ghost/ghost.html");
    }

    protected function get_host() {
        $str = (string)$this->config->api_url;
        if($str[strlen($str) - 1] === "/") $str = substr($str, 0, -1);
        return $str;
    }

    public function status():int {
        return self::STATUS_CHECK_OK;
    }


}