<?php

namespace Webmention;

use Cobalt\Tasks\Task;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Drivers\Database;
use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Rct567\DomQuery\DomQuery;
use Routes\Router;

class WebmentionHandler extends Database {
    private ResponseInterface $response;
    private $mention;

    const P_CONTENT = "p-content";
    const P_AUTHOR = "p-author";

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

    public function buildLinkData(string $target, string $source, string $sourceBody, ResponseInterface $res):int {

        // If there are no .h-entry on the page, let's just look for a link instead.
        // if($result) return $this->linkDataFromBasicLinks($target, $dom, $res);
        $dom = new DomQuery($sourceBody);
        $h_entries = $dom->find(".h-entry");
        // Here we have our list of .h-entry elements. We will now search each .h-entry
        // to determine if it's a reply, like, repost, or response
        foreach($h_entries as $index => $entry) {
            $mention = new Mention();
            $mention->setSource($source);
            $mention->setDomElements($dom, $entry);
            
            $result = $mention->setTarget($target);
            $mention->discoverReply($entry);
            $mention->discoverLike($entry);
            $mention->discoverRepost($entry);
            // return $mention;
            $this->storeWebmention($mention);
        }

        return Task::TASK_FINISHED;
    }

    public function linkDataFromBasicLinks(string $target, DomQuery $dom, ResponseInterface $res):?Mention {
        $list = $dom->getElementsByTagName("a");
        /** @var DOMNode $link */
        foreach($list as $link) {
            if(!$link->hasAttributes()) continue;
            /** @var DOMNode $var */
            $href = $link->attributes->getNamedItem("href");
            if($href->value === $target) {
                $mention->discoverReply($ancestor, $link);
                // $this->isLike($ancestor, $link);
            };
        }
        return null;
    }

    private function getClosestAncestorWithClass(DOMNode $elem, $classToFind, $ref) {
        $class = $elem->attributes->getNamedItem("class");
        $exists = preg_match("/$classToFind/", $class->value);
        if($exists !== false) {
            return $elem;
        }

        if(!isset($elem->parentNode)) return false;
        return $this->getClosestAncestorWithClass($elem->parentNode, $classToFind, $ref);
    }

    public function storeWebmention(Mention $mention):int {
        $target = $mention->getTarget();
        $source = $mention->getSource();
        $path = parse_url($target);

        $query = [
            'target' => $target,
            'source' => $source,
        ];
        
        // Check if this needs to be inserted
        $exists = $this->findOne($query);
        if($exists !== null) $this->deleteOne(['_id' => $exists->_id]);

        // If it's not anything, don't insert it.
        // if(!$mention->isReply() && !$mention->isLike() && !$mention->isRepost()) return Task::TASK_FINISHED;
        $inserted = $this->insertOne($mention);
        if($inserted->getInsertedCount() === 1) return Task::TASK_FINISHED;
        return Task::GENERAL_TASK_ERROR;
    }

    public function findBySlug($path, array $options = []) {
        return $this->find($this->getQuery($path), $options);
    }

    public function countBySlug($path) {
        return $this->count($this->getQuery($path));
    }

    public function findCommentsBySlug($path, array $options = []) {
        $query = $this->getQuery($path);
        $query['comment'] = ['$exists' => true];
        return $this->find($query, array_merge(['limit' => 100], $options));
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
        $result = $this->buildLinkData($data['target'], $data['source'], $body, $response);
        if($result !== Task::TASK_FINISHED) return Task::GENERAL_TASK_ERROR;
        return $result;
    }
}