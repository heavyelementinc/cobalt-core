<?php

namespace Cobalt\Pages\Controllers;

use Cobalt\Maps\GenericMap;
use Cobalt\Pages\Controllers\AbstractPageController;
use Cobalt\Pages\Classes\PageManager;
use Cobalt\Pages\Models\PageMap;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

/** @package  */
class LandingPages extends AbstractPageController {


    // static public function route_details_create(?array $option = null): array { 
    //     return static::permission("Pages_create",$option);
    // }

    // static public function route_details_read(?array $options = []): array { 
    //     return static::permission("Pages_read",$options);
    // }

    // static public function route_details_index(?array $options = []): array { 
    //     return static::permission("Pages_index",$options);
    // }

    // static public function route_details_update(?array $options = null): array { 
    //     return static::permission("Pages_update",$options);
    // }

    // static public function route_details_destroy(?array $options = null): array { 
    //     return static::permission("Pages_destroy",$options);
    // }

    // private static function permission(string $value, ?array $options): array {
    //     return array_merge(['permission' => "$value",], $options);
    // }

    public function get_manager(): Database {
        
        return new PageManager();
    }
    
    public function get_schema($data): GenericMap {
        return new PageMap();
    }

    public function destroy(GenericMap|BSONDocument $document): array {
        return ['message' => "Are you sure you want to delete this page?"];
    }


    // public function get_page_data($query): ?PageMap {
    //     $map = new PageMap([
    //         'h1' => get_custom("website_h1"),
    //         'title' => get_custom("website_h1"),
    //         'subtitle' => get_custom('website_h2'),
    //         'summary' => get_custom('website_bio'),
    //         'body' => get_custom('website_body'),
    //         'bio' => get_custom('website_bio'),
    //         'style' => get_custom('website_style'),
    //         'cta' => get_custom('website_cta'),
    //         'cta_href' => get_custom('website_cta_href'),
    //     ]);
    //     return $map;
    // }

}