<?php

namespace Cobalt\Posts;

use Exceptions\HTTP\NotFound;

class PostManager extends \Drivers\Database {

    public function get_collection_name() {
        return "posts";
    }

    public function get_schema_name($doc = []) {
        return "\\Cobalt\\Posts\\PostSchema";
    }

    public function get_public_name():string {
        if($this::class === "\\Cobalt\\Posts\\PostManager") return __APP_SETTINGS__['Posts']['default_name'];
        return "Posts";
    }

    final public function getTemplate($type = "post"):string {
        $types = [
            'post' => "/posts/pages/individual.html",
            'public' => "/posts/pages/index.html",
            'blurb' => "/posts/parts/index-blurb.html",
            // Admin pages
            'edit' => "/posts/admin/edit.html",
            'admin' => "/posts/admin/admin.html",
            'table' => "/posts/parts/index-admin.html",
        ];
        if(!key_exists($type,$types)) throw new NotFound("The template was not found");
        return $types[$type];
    }

    public function getPostsFromTags(array $tags, $limit = 5, $publicOnly = true):array {
        $posts = $this->findAllAsSchema([
            'tags' => $tags,
            'published' => $publicOnly,
        ],[
            'limit' => $limit,
        ]);
        return $posts;
    }
    
}