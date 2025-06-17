<?php

namespace Cobalt\Requests\Remote;

class Shopify extends API {
    public $json_parse_as_array = true;
    const API_HARD_LIMIT = 250;

    public function getOrders($status = "any", $fulfillment_status = "unfulfilled", $limit = 50, ?string $ids = null) {
        $endpoint = "https://".$this->token->key.".myshopify.com/admin/api/2022-10/orders.json?";
        $endpoint = "https://".$this->token->key.".myshopify.com/admin/api/2023-10/orders.json?";
        $requested_limit = min($limit, self::API_HARD_LIMIT);
        // if($requested_limit > $this::API_HARD_LIMIT) $requested_limit = $this::API_HARD_LIMIT;
        $query = [
            'status' => $status,
            'fulfillment_status' => $fulfillment_status,
            'limit' => $requested_limit,
            'ids' => $ids,
        ];
        if(!$query['ids']) unset($query['ids']);
        $result = $this->get($endpoint . http_build_query($query));
        $last_id = $result[count($result) - 1]['id'];
        while(count($result) < $limit) {
            $query['since_id'] = $last_id;
            $r = $this->get($endpoint . http_build_query($query));
            if(empty($r)) break;
            $new_last_id = $r[count($r) - 1]['id'];
            if($new_last_id === $last_id) break;

            $last_id = $new_last_id;

            array_push($result['orders'], ...$r['orders']);
        }
        return $result;
    }

    public function getOrder($id, ?array $fields = null) {
        $queryString = "";
        if($fields) $queryString = "?" . http_build_query(['fields' => implode(",",$fields)]);
        return $this->get("https://".$this->token->key.".myshopify.com/admin/api/2023-10/orders/$id.json$queryString");
    }
    
    public function getOrdersByIds(array $ids) {
        $commaSeparatedList = implode(",",$ids);
        
        return $this->getOrders("any", "any", count($ids), $commaSeparatedList);
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
