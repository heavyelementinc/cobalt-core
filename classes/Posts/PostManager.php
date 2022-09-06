<?php

namespace Posts;

class PostManager extends \Drivers\Database {

    public function get_collection_name() {
        return "posts";
    }

    
    
}