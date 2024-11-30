<?php

use \Cobalt\CLI\Migration;
use Cobalt\Pages\Classes\PostMap;
require_once __CLI_ROOT__ . "/migrations/pages.php";

class posts extends pages {
    public function get_persistance() {
        return new PostMap();
    }

    public function get_collection_name() {
        return "CobaltPosts";
    }
}