<?php

use Cobalt\Maps\GenericMap;
use Cobalt\Pages\PageManager;
use Cobalt\Pages\PageMap;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

/** @package  */
class LandingPages extends Controllers\Landing\Page {

    public function get_manager(): Database {
        
        return new PageManager();
    }
    
    public function get_schema($data): GenericMap {
        return new PageMap();
    }

    public function edit($document): string {
        // add_vars(["autosave" => "autosave=\"form\""]);
        return view("/pages/landing/edit.html");
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