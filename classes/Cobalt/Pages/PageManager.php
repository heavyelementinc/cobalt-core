<?php

namespace Cobalt\Pages;

use Cobalt\SchemaPrototypes\Basic\DateResult;
use Drivers\Database;
use MongoDB\BSON\UTCDateTime;

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
        $result = $this->aggregate(
            [
                ['$match' => $this->public_query([
                    'tags' => ['$in' => $tags],
                    '_id' => ['$ne' => $page->_id]
                    ])
                ],
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
                    'tag_size' => -1
                ]],
            ]
        );

        $exclude_ids = [$page->_id];
        $array = [];
        foreach($result as $i => $p) {
            if($page instanceof PostMap) $array[$i] = $p;
            else $array[$i] = (new PageMap())->ingest($p);
            $exclude_ids[] = $array[$i]->_id;
            // $array[$i]->splash_image = $p->splash_image;
            // $date = new DateResult();
            // $date->set_value($p->live_date);
            // $array[$i]->live_date = $date;
        }

        // if(count($array) < $min_recommended) {
        //     $from_author = $this->find($this->public_query([
        //         'author' => $page->author->_id(),
        //         '_id' => ['$nin' => $exclude_ids]
        //     ]), [
        //         'limit' => $min_recommended - count($array),
        //         'sort' => ['live_date' => -1],
        //         'projection' => $projection
        //     ]);
        //     foreach($from_author as $i => $post) {
        //         if($page instanceof PostMap) $array[$i] = $post;
        //         else $array[$i] = $post;
        //         // $array[$i]->splash_image = $post->splash_image;
        //         // $date = new DateResult();
        //         // $date->set_value($p->live_date);
        //         // $array[$i]->live_date = $date;
        //     }
        // }
        
        return $array;
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
}