<?php

namespace Cobalt\Pages\Controllers;

use Auth\UserCRUD;
use Cobalt\Maps\GenericMap;
use Cobalt\Pages\Controllers\AbstractPageController;
use Cobalt\Pages\Classes\PostManager;
use Cobalt\Pages\Models\PostMap;
use Drivers\Database;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

class Posts extends AbstractPageController {
    const BAD_REQUEST = 'Request contained invalid content';
    const INDEX_MODE_CLASSES = [
        POSTS_INDEX_MODE_GRID => 'index-mode--grid',
        POSTS_INDEX_MODE_FEED => 'index-mode--feed',
        POSTS_INDEX_MODE_BODY => 'index-mode--feed index-mode--body',
        POSTS_INDEX_MODE_LATEST => 'index-mode--redirect'
    ];
    public function get_manager(): Database {
        // return new PageManager(null, __APP_SETTINGS__['Posts_collection_name']);
        return new PostManager();
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

    private $valid_public_index_params = [
        'tag',
        'author',
        'page',
        'sort',
        'limit',
    ];

    public function posts_landing() {
        if(__APP_SETTINGS__['Posts_index_mode'] === POSTS_INDEX_MODE_LATEST) return $this->redirect_to_latest();
        $query = [];
        $options = [
            'sort' => ['live_date' => -1],
            'limit' => __APP_SETTINGS__['Posts_index_post_count'],
            'skip' => 0
        ];
        $misc = ['page' => 0, 'next_label' => 'Older Posts', 'prev_label' => 'Newer Posts'];
        $title = __APP_SETTINGS__['Posts_default_name'];
        $filter = "";
        
        foreach($_GET as $key => $value) {
            if($key === "uri") continue;
            if(!in_array($key, $this->valid_public_index_params)) {
                throw new BadRequest("Invalid param " . htmlentities($key), self::BAD_REQUEST);
            }
        }
        if(isset($_GET['limit'])) $this->query_limit($query, $options, $misc, $filter);
        if(isset($_GET['author'])) $this->query_for_author($query, $options, $misc, $filter);
        if(isset($_GET['tag'])) $this->query_for_tags($query, $options, $misc, $filter);
        if(isset($_GET['sort'])) $this->query_sort($query, $options, $misc, $filter);
        if(isset($_GET['page'])) $this->query_page($query, $options, $misc, $filter);
        
        $query = $this->manager->public_query($query, false);
        $count = $this->manager->count($query);
        $result = $this->manager->find($query, $options);

        $posts = "";
        $index_class = $this::INDEX_MODE_CLASSES[__APP_SETTINGS__['Posts_index_mode']];
        switch(__APP_SETTINGS__['Posts_index_mode']) {
            case POSTS_INDEX_MODE_FEED:
            case POSTS_INDEX_MODE_BODY:
                $this->render_feed($posts, $result);
                break;
            case POSTS_INDEX_MODE_GRID:
            default:
                $this->render_grid($posts, $result);
                break;
        }

        // if(!$index) throw new NotFound("There are no posts to display");
        if(!$posts) $posts = "<p style='text-align:center'>There are no posts to show</p>";
        $next_attrs = $this->pagination_link_attrs($misc['page'] ?? 0, 1, $count, $options, $misc);
        $prev_attrs = $this->pagination_link_attrs($misc['page'] ?? 0, -1, $count, $options, $misc);
        add_vars([
            'title' => $title,
            'posts' => $posts,
            'filter' => $filter,
            'count' => ceil($count / $options['limit']),
            'page' => $misc['page'] + 1,
            'index_class' => $index_class,
            'next_page' => "<a $next_attrs>$misc[next_label] <i name='chevron-right'></i></a>",
            'prev_page' => "<a $prev_attrs><i name='chevron-left'></i> $misc[prev_label]</a>",
        ]);

        return view('/Cobalt/Pages/templates/web/post-index.php');
    }

    private function redirect_to_latest() {
        $query = $this->manager->public_query();
        $result = $this->manager->find($query, ['sort' => ['live_date' => -1],'limit' => 1]);
        if($result) {
            $result = iterator_to_array($result)[0];
            redirect_and_exit($result->url_slug->get_path());
            return;
        }
        throw new NotFound("That page does not exist", true);
    }

    private function render_grid(&$posts, $result) {
        foreach($result as $post) {
            if($post instanceof PostMap === false) $post = (new PostMap())->ingest($post);
            $posts .= $this->renderPreview($post);
        }
    }

    private function render_feed(&$posts, $result) {
        foreach($result as $post) {
            if($post instanceof PostMap === false) $post = (new PostMap())->ingest($post);
            $posts .= view("/Cobalt/Pages/templates/parts/index-feed-preview.php", ['page' => $post]);
        }
    }

    private function query_for_author(array &$query, array &$options, array &$misc, string &$filter) {
        $uman = new UserCRUD();
        $user = $uman->getUserByUsername($_GET['author']);
        if(!$user) throw new NotFound("Author not found", true);
        if(!has_permission("Post_allowed_author", null, $user)) throw new NotFound("Author not found", true);
        $query['author'] = $user->_id;
        $filter .= "<div class='cobalt-posts--index-filter'>Showing posts by " . strip_tags((string)$user->display_name) . "</div>";
    }

    private function query_for_tags(array &$query, array &$options, array &$misc, string &$filter) {
        $strippedTags = strip_tags((string)$_GET['tag']);
        if($_GET['tag'] !== $strippedTags) throw new BadRequest('Tags appear to be malformed', self::BAD_REQUEST);
        $query['tags'] = $_GET['tag'];
        $filter .= "<div class='cobalt-posts--index-filter'>Filtering by tag \"" . htmlspecialchars($strippedTags) . "\"</div>";
    }

    private function query_sort(array &$query, array &$options, array &$misc, string &$filter) {
        $sort = filter_var($_GET['sort'] ?? -1, FILTER_VALIDATE_INT, [
            'default' => -1,
            'min_range' => -1,
            'max_range' => 1,
        ]);
        $options['sort']['live_date'] = $sort;
        if($options['sort']['live_date'] === false) throw new BadRequest("Invalid 'sort' parameter", self::BAD_REQUEST);
        if($options['sort']['live_date'] === 0) $options['sort']['live_date'] = -1;
        switch($sort) {
            case 1:
                $misc['next_label'] = "Newer Posts";
                $misc['prev_label'] = "Older Posts";
        }

    }

    private function query_page(array &$query, array &$options, array &$misc, string &$filter) {
        $misc['page'] = filter_var($_GET['page'] ?? 0, FILTER_VALIDATE_INT);
        if(!is_int($misc['page'])) throw new BadRequest("Invalid 'page' parameter", self::BAD_REQUEST);
        $options['skip'] = $options['limit'] * $misc['page'];
    }

    private function query_limit(array &$query, array &$options, array &$misc, string &$filter) {
        $max_range = min(__APP_SETTINGS__['Posts_index_post_count'] * 10, [35]);
        $limit = filter_var($_GET['limit'], 
            FILTER_VALIDATE_INT, 
            [
                'default' => __APP_SETTINGS__['Posts_index_post_count'],
                'min_range' => 3,
                'max_range' => $max_range,
            ]
        );
        if($limit == false) {
            // $limit = __APP_SETTINGS__['Posts_index_post_count'];
            throw new BadRequest("Invalid '$limit' value", self::BAD_REQUEST);
        }
        if($limit > $max_range) throw new BadRequest("Exceeded maximum limit", self::BAD_REQUEST);
        $options['limit'] = $limit;
    }


    private function pagination_link_attrs(int $current_page, int $next_page, int $total_count, array $options, array $misc) {
        $target_page = $current_page + $next_page;
        $attrs = [];
        switch($next_page) {
            case -1:
                if($target_page < 0 ){
                    $attrs['disabled'] = "disabled";
                }
                break;
            case 1:
            default:
                if($target_page >= ceil($total_count / $options['limit'])) {
                    $attrs['disabled'] = "disabled";
                }
                break;
        }
        $request = $_GET;
        unset($request['uri']);
        $attrs['href'] = __APP_SETTINGS__['Posts_public_index'] . "?" . http_build_query(array_merge($request, ['page' => $target_page]));
        return associative_array_to_html_attributes($attrs);
    }
    
    public function rss_feed() {
        $docs = $this->manager->find($this->manager->public_query([], __APP_SETTINGS__['Posts_rss_feed_include_unlisted']));

        header('Content-Type: application/rss+xml; charset=utf-8');

        $items = "";//$this->docsToViews($docs, "/RSS/item.xml");
        foreach($docs as $doc) {
            $items .= view("/Cobalt/Pages/templates/RSS/item.xml", ['doc' => $doc]);
        }
        echo view("/Cobalt/Pages/templates/RSS/feed.xml", ['posts' => $items]);
        exit;
    }
}