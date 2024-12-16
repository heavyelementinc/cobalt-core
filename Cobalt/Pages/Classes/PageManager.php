<?php

namespace Cobalt\Pages\Classes;

use Cobalt\Pages\Models\PageMap;
use Cobalt\Pages\Models\PostMap;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\Tasks\Task;
use Drivers\Database;
use Exception;
use IndieWeb\MentionClient;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PEAR2\Services\Linkback\Client;
use Tasks;
use Webmention\WebmentionDocument;

class PageManager extends Database {

    const PREVIEW_PROJECTION = [
        '__pclass' => 1,
        '_id' => 1,
        'title' => 1,
        'subtitle' => 1,
        'summary' => 1,
        'url_slug' => 1,
        'splash_image' => 1,
        'splash_image_alignment' => 1,
        'live_date' => 1,
        'views' => 1,
        'time_to_read' => 1,
        'tags' => 1,
    ];

    function __construct($database = null, $collection = null) {
        parent::__construct($database, $collection);
    }

    public function public_query(array $additional = [], bool $includeUnlisted = true):array {
        $visibility = '$gt';
        if($includeUnlisted) $visibility = '$gte';

        return array_merge([
            'visibility' => [$visibility => PageMap::VISIBILITY_UNLISTED],
            'live_date' => ['$lte' => new UTCDateTime(time() * 1000)]
        ], $additional);
    }

    public function get_collection_name() {
        return COBALT_PAGES_DEFAULT_COLLECTION;
    }
    
    function getRelatedPages(PageMap $page, ?array $projection = null):array {
        $default_projection = self::PREVIEW_PROJECTION;

        if($projection === null) $projection = $default_projection;
        else $projection = array_merge($projection, $default_projection);

        $min_recommended = $page->max_related->getValue();
        $tags = $page->tags->getRaw();
        $result = $this->aggregate($this->getRelatedPagePipeline(
            $tags, $page->_id, $projection,
        ));

        $exclude_ids = [$page->_id];
        $array = [];
        foreach($result as $i => $p) {
            if($page instanceof PostMap) $array[$i] = $p;
            else $array[$i] = (new PageMap())->ingest($p);
            $exclude_ids[] = $array[$i]->_id;
        }

        if(count($array) < $min_recommended) {
            $from_author = $this->find($this->public_query([
                'author' => $page->author->_id(),
                '_id' => ['$nin' => $exclude_ids]
            ]), [
                'limit' => $min_recommended - count($array),
                'sort' => ['live_date' => -1],
                'projection' => $projection
            ]);
            foreach($from_author as $i => $post) {
                if($page instanceof PostMap) $array[$i] = $post;
                else $array[$i] = $post;

            }
        }
        
        return $array;
    }

    function getRelatedPagePipeline(array $tags, ?ObjectId $exclude_id = null, array $projection = []) {
        $public_query = [
            'tags' => ['$in' => $tags],
        ];

        if($exclude_id) $public_query['_id'] = ['$ne' => $exclude_id];

        $pipeline = [
            ['$match' => $this->public_query($public_query)],
            ['$project' => array_merge(
                $projection, [
                'tags_intersection' => [
                    '$setIntersection' => [$tags, '$tags']
                ]]
            )],
            ['$project' => array_merge(
                $projection, [
                'tags_intersection' => 1,
                'tag_size' => [
                    '$size' => '$tags_intersection'
                ]]
            )],
            ['$sort' => [
                'tag_size' => -1,
                'live_date' => -1,
            ]],
        ];
        return $pipeline;
    }

    function getPagesFromTags(array $tags, int $limit = 3) {
        return $this->find($this->public_query([
            'tags' => ['$in' => $tags]]
        ), [
            'sort' => [
                'live_date' => -1
            ],
            'limit' => $limit
        ]);
    }

    function webmention_send_task(Task $task):int {
        // $_id = new ObjectId($id);
        $_id = $task->get_for();
        /** @var WebmentionDocument $doc */
        $doc = $this->findOne(['_id' => $_id]);
        if(!$doc) return Task::TASK_FINISHED;
        if($doc->webmention_is_locked()) return Task::GENERAL_TASK_ERROR;
        $doc->webmention_lock();
        $responses = [];
        try {
            foreach($doc->webmention_get_urls_to_notify() as $link) {
                $this->send_linkback($link, $doc, $responses);
            }
        } catch(Exception $e) {
            $doc->webmention_unlock();
            return Task::GENERAL_TASK_ERROR;
        }

        $doc->webmention_unlock();
        return Task::TASK_FINISHED;
    }

    // private function send_linkback_old() {
    //     $linkbackClient = new Client();
    //     $request = $linkbackClient->getRequest();
    //     $request->setConfig([
    //         'ssl_verify_peer' => false,
    //         'ssl_verify_host' => false,
    //     ]);
    //     $request->setHeader('user-agent', 'Cobalt Engine Webmention Discovery Bot');
    //     $linkbackClient->setRequestTemplate($request);
    //     $response = $linkbackClient->send($doc->webmention_get_canonincal_url(), $link);
    //     array_push($responses, $response);
    // }

    private function send_linkback($link, WebmentionDocument $doc, &$responses): void {
        $client = new MentionClient();
        $client::setUserAgent('Cobalt Engine Webmention Discovery Bot');
        $url = $doc->webmention_get_canonincal_url();
        $responses[] = $client->sendWebmention($url, $link);
        $responses[] = $client->sendPingback($url, $link);
        // $client->sendPingback($link, $doc->webmention_get_canonical_url());
        // $client->sendWebmention($url, $link);
        return;
    }
}