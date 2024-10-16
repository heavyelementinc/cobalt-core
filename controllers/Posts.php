<?php

use Auth\UserCRUD;
use Cobalt\Maps\GenericMap;
use Cobalt\Pages\PageManager;
use Cobalt\Pages\PostMap;
use Controllers\Landing\Page;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

class Posts extends Page {

    public function get_manager(): Database {
        return new PageManager(null, __APP_SETTINGS__['Posts']['collection_name']);
    }

    public function get_schema($data): GenericMap {
        return new PostMap();
    }

    public function destroy(GenericMap|BSONDocument $document): array {
        return [
            'message' => "Are you sure you want to delete \"$document->title\"? There's no undoing this operation",
            'post' => $_POST,
        ];
    }

    public function posts_landing() {
        $query = [];
        if(isset($_GET['tag'])) $query['tags'] = $_GET['tag'];
        if(isset($_GET['author'])) {
            $uman = new UserCRUD();
            $user = $uman->getUserByUsername($_GET['author']);
            if(!$user) throw new NotFound("Author not found", true);
            if(!has_permission("Posts_allowed_author", null, $user)) throw new NotFound("Author not found", true);
            $query['author'] = $user->_id;
        }
        $result = $this->manager->find(
            $this->manager->public_query($query, false),
            [
                'sort' => [
                    'live_date' => -1
                ],
                // 'projection' => $this->manager::PREVIEW_PROJECTION
            ]
        );
        $posts = "";
        foreach($result as $post) {
            if($post instanceof PostMap === false) $post = (new PostMap())->ingest($post);
            $posts .= $this->renderPreview($post);
        }
        // if(!$index) throw new NotFound("There are no posts to display");
        if(!$posts) $posts = "<p style='text-align:center'>There are no posts to show</p>";
        add_vars([
            'title' => __APP_SETTINGS__['Posts']['default_name'],
            'posts' => $posts,
        ]);

        return view('/posts/pages/index.html');
    }
    
    public function rss_feed() {
        $docs = $this->manager->find($this->manager->public_query([], __APP_SETTINGS__['Posts_rss_feed_include_unlisted']));

        header('Content-Type: application/rss+xml; charset=utf-8');

        $items = "";//$this->docsToViews($docs, "/RSS/item.xml");
        foreach($docs as $doc) {
            $items .= view("/RSS/item.xml", ['doc' => $doc]);
        }
        echo view("/RSS/feed.xml", ['posts' => $items]);
        exit;
    }
}