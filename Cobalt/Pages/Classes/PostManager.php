<?php
namespace Cobalt\Pages\Classes;

use Cobalt\Pages\Classes\PageManager;

class PostManager extends PageManager {
    public function get_collection_name() {
        return __APP_SETTINGS__['Posts_collection_name'];
    }
}