<?php

namespace Webmention;

use Cobalt\Tasks\Task;
use DOMDocument;
use Drivers\Database;
use Exception;
use HTTP_Request2;
use HTTP_Request2_Response;
use PEAR2\Services\Linkback\Server as LinkbackServer;
use PEAR2\Services\Linkback\Server\Callback\ILink;
use PEAR2\Services\Linkback\Server\Callback\ISource;
use PEAR2\Services\Linkback\Server\Callback\IStorage;
use PEAR2\Services\Linkback\Server\Callback\ITarget;
use Routes\Router;

class Server extends Database implements ITarget, ISource, ILink, IStorage {

    public function get_collection_name() {
        return "webmentions";
    }

    public function verifyTargetExists($target) {
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

    public function fetchSource($url) {
        $client = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
        $response = $client->send();
        return $response;
    }

    public function verifyLinkExists($target, $source, $sourceBody, HTTP_Request2_Response $res) {
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

    public function storeLinkback($target, $source, $sourceBody, HTTP_Request2_Response $res) {
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
        $restore = $_POST;
        $_POST = $task->get_additional_data(); // Criminally stupid workaround for the LinkbackServer library.
        $server = new LinkbackServer(); // This library fucking sucks
        $server->addCallback(new Server());
        $server->run();
        $_POST = $restore;
        return Task::TASK_FINISHED;
    }
}