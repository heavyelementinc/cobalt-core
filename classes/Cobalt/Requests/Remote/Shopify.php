<?php

namespace Cobalt\Requests\Remote;

class Shopify extends API {
    public $json_parse_as_array = true;

    public function getOrders($status = "any") {
        return $this->get("https://".$this->token->key.".myshopify.com/admin/api/2022-10/orders.json?status=$status");
    }

    public function getOrder($id, ?array $fields = null) {
        $queryString = "";
        if($fields) $queryString = "?" . http_build_query(['fields' => implode(",",$fields)]);
        return $this->get("https://".$this->token->key.".myshopify.com/admin/api/2022-10/orders/$id.json$queryString");
    }



    public function getIfaceName(): string {
        return "\\Cobalt\\Requests\\Tokens\\Shopify";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function refreshTokenCallback($result): string {
        return "";
    }

    public static function getMetadata(): array {
        return [
            'icon' => "<i name='shopping'></i>",
            'name' => "Shopify"
        ];
    }

    public function testAPI(): bool {
        return true;
    }
}
