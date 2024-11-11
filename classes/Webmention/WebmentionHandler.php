<?php

namespace Webmention;

use Cobalt\Tasks\Task;
use DOMDocument;
use Drivers\Database;
use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Routes\Router;

class WebmentionHandler extends Database {
    private ResponseInterface $response;

    public function get_collection_name() {
        return "webmentions";
    }

    /**
     * Verify that the local target is a valid page on this server
     * @param string $target The URL on this server
     * @return bool
     **/
    public function verifyTargetExists(string $target):bool {
        $url = parse_url($target);

        // Check if the hostname is valid
        if($url['host'] !== __APP_SETTINGS__['domain_name']) {
            if(!in_array($url['host'], __APP_SETTINGS__['API_CORS_allowed_origins'])) {
                return false;
            }
        }

        // Let's check if our target exists
        global $ROUTER;
        if($ROUTER == null) $ROUTER = new Router("web", "get");
        $ROUTER->init_route_table();
        $ROUTER->get_routes();

        $route = $ROUTER->discover_route($url['path'], $url['query'], "get", "web");
        if(is_array($route)) return true;
        return false;
    }

    public function fetchSource(string $url) {
        $client = new Client();
        $this->response = $client->request("GET", $url);
        return $this->response;
    }

    public function verifyLinkExists(string $target, string $source, string $sourceBody, ResponseInterface $res) {
        $dom = new DOMDocument();
        $dom->loadHTML($sourceBody);

        $list = $dom->getElementsByTagName("a");
        foreach($list as $link) {
            if(!$link->hasAttributes()) continue;
            $href = $link->attributes->getNamedItem("href");
            if($href === $target) return true;
        }

        return false;
    }

    public function storeWebmention(string $target, string $source, string $sourceBody, ResponseInterface $res):void {
        $path = parse_url($target);
        $url = $path;
        $url['withQuery'] = ($url['query']) ? "$url[path]?$url[query]" : "$url[path]";
        $doc = [
            'target' => $target,
            'source' => $source,
            'url' => $url,
        ];

        $this->updateOne($doc, [
            '$set' => $doc
        ], [
            'upsert' => true
        ]);
    }

    public function findBySlug($path) {
        return $this->find($this->getQuery($path));
    }

    public function countBySlug($path) {
        return $this->count($this->getQuery($path));
    }

    public function getQuery($path){
        return [
            '$or' => [
                [
                    'url.path' => $path
                    
                ],
                [
                    'url.withQuery' => $path
                ]
            ]
        ];
    }

    public function process_task(Task $task):int {
        $data = $task->get_additional_data();
        $doesTargetExist = $this->verifyTargetExists($data['target']);
        if(!$doesTargetExist) return Task::GENERAL_TASK_ERROR;
        $response = $this->fetchSource($data['source']);
        if($response->getStatusCode() >= 300) return Task::GENERAL_TASK_ERROR;
        $body = $response->getBody();
        $doesLinkExist = $this->verifyLinkExists($data['target'], $data['source'], $body, $response);
        if(!$doesLinkExist) return Task::GENERAL_TASK_ERROR;
        $this->storeWebmention($data['target'], $data['source'], $body, $response);
        
        return Task::TASK_FINISHED;
    }
}