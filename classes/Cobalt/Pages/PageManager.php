<?php

namespace Cobalt\Pages;

use Drivers\Database;
use MongoDB\BSON\UTCDateTime;

class PageManager extends Database {

    const PREVIEW_PROJECTION = [
        '_id' => 1,
        'title' => 1,
        'subtitle' => 1,
        'summary' => 1,
        'url_slug' => 1,
        'splash_image' => 1,
        'splash_image_alignment' => 1,
    ];

    function __construct($database = null, $collection = null) {
        parent::__construct($database, $collection);
    }

    public function public_query(array $additional = []):array {
        return array_merge([
            'visibility' => (string)PageMap::VISIBILITY_PUBLIC,
            'live_date' => ['$lte' => new UTCDateTime(time() * 1000)]
        ], $additional);
    }

    public function get_collection_name() {
        return "CobaltPages";
    }
    
    function getRelatedPages(PageMap $page, ?array $projection = null):array {
        $default_projection = self::PREVIEW_PROJECTION;

        if($projection === null) $projection = $default_projection;
        else $projection = array_merge($projection, $default_projection);

        $min_recommended = $page->max_related->getValue();
        $tags = $page->tags->getRaw();
        $result = $this->aggregate(
            [
                ['$match' => $this->public_query([
                    'tags' => ['$in' => $tags],
                    '_id' => ['$ne' => $page->_id]
                    ])
                ],
                ['$project' => array_merge($projection, [
                    'tags_intersection' => [
                        '$setIntersection' => [$tags, '$tags']
                    ],
                ])],
            ]
        );

        $array = [];
        foreach($result as $i => $p) {
            if($page instanceof PostMap) $array[$i] = (new PostMap())->ingest($p ?? []);
            else $array[$i] = (new PageMap())->ingest($p);
            $array[$i]->splash_image = $p->splash_image;
        }

        if(count($array) < $min_recommended) {
            $from_author = $this->find($this->public_query([
                'author' => $page->author->_id(),
                '_id' => ['$ne' => $page->_id]
            ]), [
                'limit' => $min_recommended,
                'sort' => ['live_date' => -1],
                'projection' => $projection
            ]);
            foreach($from_author as $i => $post) {
                if($page instanceof PostMap) $array[$i] = (new PostMap())->ingest($post ?? []);
                else $array[$i] = (new PageMap())->ingest($post);
                $array[$i]->splash_image = $post->splash_image;
            }
        }
        
        return $array;
    }
}