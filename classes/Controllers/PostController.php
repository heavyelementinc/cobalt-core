<?php

namespace Controllers;

use Cobalt\Maps\GenericMap;
use Cobalt\Pages\PageManager;
use Cobalt\Pages\PostMap;
use Controllers\Landing\Page;
use Drivers\Database;
use MongoDB\Model\BSONDocument;

class PostController extends Page {

    /**
     * 
     * @return PageManager
     */
    public function get_manager(): Database {
        return new PageManager();
    }

    /**
     * 
     * @param mixed $data 
     * @return PostMap
     */
    public function get_schema($data): GenericMap {
        return new PostMap();
    }

    public function edit($document): string {
        return view("/pages/landing/edit.html");
    }

    public function destroy(GenericMap|BSONDocument $document): array {
        return ['message' => "Are you sure you want to delete $document->title?"];
    }

}