<?php

namespace Cobalt\Pages;

use Drivers\Database;
use MongoDB\BSON\UTCDateTime;

class PageManager extends Database {

    function __construct() {
        parent::__construct();
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
        $default_projection = [
            '_id' => 1,
            'title' => 1,
            'subtitle' => 1,
            'summary' => 1,
            'url_slug' => 1,
            'splash_image' => 1,
            'splash_image_alignment' => 1,
        ];
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
            $array[$i] = (new PageMap())->ingest($p);
            $array[$i]->splash_image = $p->splash_image;
        }

        if(count($array) < $min_recommended) {
            $from_author = $this->find($this->public_query([
                'author' => $page->author->getValue(),
                '_id' => ['$ne' => $page->_id]
            ]), [
                'limit' => $min_recommended,
                'sort' => ['live_date' => -1],
                'projection' => $projection
            ]);
            foreach($from_author as $post) {
                $array[] = $post;
            }
        }
        
        return $array;
    }
}