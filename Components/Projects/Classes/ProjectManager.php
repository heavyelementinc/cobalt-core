<?php

namespace Components\Projects\Classes;

use Drivers\Database;
use Exception;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;

class ProjectManager extends Database {

    public function get_collection_name() {
        return "projects";
    }

    public function get_schema_name($doc = [])
    {
         return "\\Components\\Projects\\Classes\\ProjectSchema";
    }
    
    public function projectIndex($shop = false, $ignoreSlug = null, $limit = 20) {
        $query = [
            'published' => true,
            'shop'      => $shop,
        ];
        if($ignoreSlug) $query['url'] = ['$ne' => $ignoreSlug];
        return $this->find($query, ['sort' => ['order' => -1],'limit' => $limit,]);
        // $projects = $this->getLatestProjects();
        
        // return implode("",array_map(fn ($val) => view("/pages/projects/index-listing.html", ['doc' => $val]), $projects));
    }
    public function sortOrder() {
        return ['sort' => ['order' => 1, "_id" => 1]];
    }
    
    public function project($id) {
        // foreach($this->dummy as $data) {
        //     if($data['url'] === $id) return new ProjectSchema($data);
        // }
        
        // try{
        //     @$query = ['_id' => new ObjectId($id)];
        // } catch(Exception $e) {
        $query = ['url' => $id];
        // }
        $result = $this->findOneAsSchema($query);
        return $result ?? throw new NotFound("That project doesn't exist");
    }

    function getLatestProjects() {
        // return array_map(fn ($val) => new ProjectSchema($val),$this->dummy);
    }

    // var $dummy = [
    //     [
    //         '_id' => '0',
    //         'order' => 1,
    //         'name' => 'The Updated Traditional',
    //         'url' => 'updated-traditional',
    //         'blurb' => 'Classic period architectural lines relaxed by comfortably casual slip-covered furnishings.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '1',
    //         'order' => 2,
    //         'name' => 'The Collected Home',
    //         'url' => 'collected-home',
    //         'blurb' => 'A unique mix of items from around the world blended with a collection of new furnishings.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '2',
    //         'order' => 3,
    //         'name' => 'The Elevated Barn',
    //         'url' => 'elevated-barn',
    //         'blurb' => 'Comfort and warmth envelope each individual space in an open-plan barn renovation.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '0',
    //         'order' => 1,
    //         'name' => 'The Updated Traditional',
    //         'url' => 'updated-traditional',
    //         'blurb' => 'Classic period architectural lines relaxed by comfortably casual slip-covered furnishings.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '1',
    //         'order' => 2,
    //         'name' => 'The Collected Home',
    //         'url' => 'collected-home',
    //         'blurb' => 'A unique mix of items from around the world blended with a collection of new furnishings.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '2',
    //         'order' => 3,
    //         'name' => 'The Elevated Barn',
    //         'url' => 'elevated-barn',
    //         'blurb' => 'Comfort and warmth envelope each individual space in an open-plan barn renovation.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '0',
    //         'order' => 1,
    //         'name' => 'The Updated Traditional',
    //         'url' => 'updated-traditional',
    //         'blurb' => 'Classic period architectural lines relaxed by comfortably casual slip-covered furnishings.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '1',
    //         'order' => 2,
    //         'name' => 'The Collected Home',
    //         'url' => 'collected-home',
    //         'blurb' => 'A unique mix of items from around the world blended with a collection of new furnishings.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '2',
    //         'order' => 3,
    //         'name' => 'The Elevated Barn',
    //         'url' => 'elevated-barn',
    //         'blurb' => 'Comfort and warmth envelope each individual space in an open-plan barn renovation.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '0',
    //         'order' => 1,
    //         'name' => 'The Updated Traditional',
    //         'url' => 'updated-traditional',
    //         'blurb' => 'Classic period architectural lines relaxed by comfortably casual slip-covered furnishings.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '1',
    //         'order' => 2,
    //         'name' => 'The Collected Home',
    //         'url' => 'collected-home',
    //         'blurb' => 'A unique mix of items from around the world blended with a collection of new furnishings.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    //     [
    //         '_id' => '2',
    //         'order' => 3,
    //         'name' => 'The Elevated Barn',
    //         'url' => 'elevated-barn',
    //         'blurb' => 'Comfort and warmth envelope each individual space in an open-plan barn renovation.',
    //         'primary' => 0,
    //         'images' => [
    //             [
    //                 'filename' => '/res/img/work/Weber Barn.jpeg',
    //                 'thumb' => '/res/img/work/Weber Barn.jpeg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Bishop Great Room.jpeg',
    //                 'thumb' => '/res/img/work/Bishop Great Room.jpeg',
    //                 // 'meta' => 
    //             ],
    //             [
    //                 'filename' => '/res/img/work/Kahn Great Room.jpg',
    //                 'thumb' => '/res/img/work/Kahn Great Room.jpg',
    //                 // 'meta' =>/res/img/work/Weber Barn.jpeg 
    //             ],
    //         ]
    //     ],
    // ];
}