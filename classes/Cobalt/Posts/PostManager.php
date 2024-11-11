<?php
namespace Cobalt\Posts;

use Cobalt\Pages\PageManager;

class PostManager extends PageManager {
    public function get_collection_name() {
        return __APP_SETTINGS__['Posts']['collection_name'];
    }
}