<?php

use \Cobalt\CLI\Migration;
use Cobalt\Pages\Models\PostMap;
require_once __CLI_ROOT__ . "/migrations/pages.php";

class markdown_posts_to_persistant_posts extends pages {
    public function get_persistance() {
        return new PostMap();
    }

    public function get_collection_name() {
        return "CobaltPosts";
    }
}