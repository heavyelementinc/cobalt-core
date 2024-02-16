<?php

namespace Cobalt\Requests\Remote;

use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\HTTPException;
use stdClass;

class Mastodon extends API {

    public function getIfaceName(): string {
        return "Mastodon";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function testAPI(): bool {
        return true;
    }

    public static function getMetadata(): array {
        return [
            'icon' => "<i name='mastodon'></i>",
            'name' => "Mailchimp",
            'view' => "/admin/api/mastodon.html"
        ];
    }
}
