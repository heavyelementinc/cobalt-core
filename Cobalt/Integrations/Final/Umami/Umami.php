<?php

namespace Cobalt\Integrations\Final\Umami;

use Cobalt\Integrations\Base;
use Override;
use Cobalt\Integrations\Config;

class Umami extends Base {
    #[Override]
    public function publicName(): string
    {
        return "Umami";
    }

    #[Override]
    public function publicIcon(): string
    {
        return "";
    }

    #[Override]
    public function get_unique_token(): string {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function configuration(): Config {
        return new UmamiConfig();
    }

    #[Override]
    public function status(): int
    {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function html_token_editor(): string
    {
        return 
    }

}