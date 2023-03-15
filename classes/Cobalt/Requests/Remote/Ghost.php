<?php
namespace Cobalt\Requests\Remote;

use Cobalt\Requests\Remote\API;

class Ghost extends API {

    public function getIfaceName(): string {
        return "\\Cobalt\\Requests\\Tokens\\Ghost";
    }

    public function getPaginationToken(): array {
        return [];
    }

    public function testAPI(): bool {
        return true;
    }

    public static function getMetadata(): array {
        return [
            'icon' => "<i name='ghost'></i>",
            'name' => "Ghost",
            'view' => "/admin/api/editors/ghost.html"
        ];
    }

    public function fetchMembers($include = ["newsletters","labels"]){
        $url = $this->token->key."/ghost/api/admin/members/?";
        $page = 1;
        $limit = 15;
        $results = [];
        while(true) {
            $params = http_build_query([
                'include' => implode(",",$include),
                'page' => $page,
                'limit' => $limit,
            ]);
            $response = $this->get($url . $params);
            foreach($response->members as $member) {
                array_push($results, $member);
            }
            if($response->meta->pagination->next === null) break;
            $page = $response->meta->pagination->next;
            if($page > $response->meta->pagination->pages) break;
        }
        return $results;
    }

}
