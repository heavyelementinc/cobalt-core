<?php
/** Using the Post Contoller:
 * Have your controller extend PostController,
 * In your controller constructor, call $this->initialize("<the Post's DB collection>", "the Post's schema");
 * Once you've done that, you only need to set up your routes to call `YourPostController@update`, etc.
 * 
 * HOWEVER, do note that in order to create a Post, you'll need to either enable the default posts in your settings.json
 * OR extend this controller with one of your own.
 */
namespace Controllers;

use Cobalt\Posts\PostManager;
use Cobalt\Posts\PostSchema as PostSchema;
use Exception;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\PostNotFound;
use Exceptions\HTTP\Unauthorized;
use Exceptions\HTTP\UnknownError;

abstract class PostController extends Controller {

    public $postMan = null;
    protected $permission = "Post_manage_posts";
    protected $publishPermission = "Post_publish_posts";
    protected $permissionGroup = "Post";
    protected $customTitle = "";

    public function initialize($collection, $schemaName = null, $permission_suffix = "") {
        if($schemaName === null) $schemaName =  "\\Cobalt\\Posts\\PostSchema";
        $this->init_permission($permission_suffix);
        
        // Initialize our Post controller
        $this->postMan = new PostManager(null, $collection);
        $this->postMan->set_schema($schemaName);

    }

    public function admin_index() {
        if(!$this->postMan) throw new Exception("You must manually initialize the PostController");
        $result = $this->postMan->findAllAsSchema(...$this->params($this->postMan, [], [
            'defaultOptions' => ['sort' => ['publicationDate' => -1]]
        ]));
        $posts = "";
        foreach($result as $post) {
            $posts .= view($post->getTemplate("table"),[
                'post' => $post,
                'editor_route' => $this->path('edit',[(string)$post['_id']],"get","admin"),
            ]);
        }
        add_vars([
            'title' => ($this->customTitle) ? $this->customTitle . " Admin Panel" : 'Posts Admin Panel',
            'posts' => $posts,
            'controls' => $this->getPaginationLinks(),
            'new_post_link' => $this->path('edit',[(string)$this->postMan->__id()],"get","admin")
        ]);

        if(!$post) {
            $schema = $this->postMan->get_schema_name();
            $post = new $schema();
        }

        set_template($post->getTemplate('admin'));
    }

    public function edit($id = null) {
        if(!$this->postMan) throw new Exception("You must manually initialize the PostController");
        $post = $this->postMan->findOneAsSchema(['_id' => $this->postMan->__id($id)]);
        if(!$post) {
            $schema = $this->postMan->get_schema_name();
            $post = new $schema(['_id' => $this->postMan->__id($id)]);
        }
        add_vars([
            'title' => "Edit",
            'post' => $post,
            'href' => $this->path('post',[]),
            'update_action' => $this->path('update',[$id],'put',   'apiv1'),
            'upload_action' => $this->path('upload',[$id],'post',  'apiv1'),
            'delete_action' => $this->path('delete',[$id],'delete','apiv1'),
            'pretty' => "<fold-out title=\"Raw Database Entry\"><pre>".json_encode($post, JSON_PRETTY_PRINT)."</pre></fold-out>",
        ]);

        set_template($post->getTemplate("edit"));
    }

    public function update($id = null) {
        if(!$this->postMan) throw new Exception("You must manually initialize the PostController");

        // Validate our permissions:
        if(!has_permission($this->permission,$this->permissionGroup)) throw new Unauthorized("You're not authorized to manage Post posts.");
        if(!has_permission($this->publishPermission,$this->permissionGroup)) {
            unset($_POST['published']);
            unset($_POST['publicationDate']);
        }
        // Let's run our schema
        $schema = $this->postMan->__schema;
        $validation = new $schema();
        $mutant = $validation->validate($_POST);
        $_id = $this->postMan->__id($id);
        // Find our post
        $this->postMan->updateOne(['_id' => $_id],[
            '$set' => $mutant
        ],['upsert' => true]);

        // We want to redirect new entries to the appropriate page
        if($id === null) header("X-Redirect: " . $this->path('update',[(string)$_id]),"get","admin");
        return new $schema($mutant);
    }

    public function deletePost($id) {
        if(!$this->postMan) throw new Exception("You must manually initialize the PostController");
        if(!has_permission($this->permission,$this->permissionGroup)) throw new Unauthorized("You're not authorized to manage Post posts.");
        $_id = $this->postMan->__id($id);
        $post = $this->postMan->findOneAsSchema(['_id' => $_id]);
        if(!$post) throw new NotFound("Entry does not exist");
        confirm("Are you sure you want to delete \"".htmlspecialchars($post->title)."\"? There's no undoing this action.",$_POST);
        $result = $this->postMan->deleteOne(['_id' => $_id]);
        header("X-Redirect: " . $this->path('admin_index',[],"get","admin"));
        return $result->getDeletedCount();
    }

    public function index() {
        if(!$this->postMan) throw new Exception("The Post Controller is not initialized");
        $query = $this->params($this->postMan, ['published' => true], [
            'defaultOptions' => ['sort' => ['publicationDate' => -1]]
        ]);
        $docs = $this->postMan->findAllAsSchema(...$query);

        $og_image = "";
        $og_title = __APP_SETTINGS__ . "";
        $og_body = __APP_SETTINGS__['app_name'] . " news and updates feature the kinds of";

        $posts = "";

        foreach($docs as $index => $doc) {
            if($index === 0) $doc->prominent = true;
            if(isset($_SESSION['Posts_display_type'])) $doc->prominent = $_SESSION['Posts_display_type'];
            $posts .= view($doc->getTemplate('blurb'), [
                'post' => $doc,
                'href' => $this->path('post',[(string)$doc['url_slug']])
            ]);
        }
        // if($docs === null) $doc = new ;
        if(!$posts) $posts = view('/posts/parts/no-posts.html',[]);
        add_vars([
            'title' => $this->postMan->get_public_name(),
            'posts' => $posts,
            'controls' => $this->getPaginationLinks(true),
        ]);

        set_template((new PostManager())->getTemplate('public'));
    }

    public function post($slug) {
        if(!$this->postMan) throw new Exception("The Post Controller is not initialized");
        $query = ['url_slug' => $slug, 'published' => true];
        
        // Determine if the user is allowed to access unpublished posts
        $is_permitted = false;
        if(session() && has_permission($this->permission)) {
            // If they are, we unset the 'published' query
            unset($query['published']);
            $is_permitted = true; // Let's also store that the user is permitted to access unpublished posts
        }

        // Query for our post and throw a not found error if we don't find anything
        $post = $this->postMan->findOneAsSchema($query);
        if(!$post) throw new PostNotFound("That post doesn't exist");

        // Let's allow an authorized user to have access an edit link
        $edit = "";
        if($is_permitted) {
            $route = $this->path('edit',[$post->_id],'get','admin');
            $edit = "<a href='$route' is>Edit this post</a>";
        }

        // If the post isn't published, we want to provide a warning about it.
        $unpublished = "";
        if(!$post['published']) $unpublished = "<div class='cobalt-post--unpublished-preview'>This post is unpublished. $edit when you're ready.</div>";

        // Compile it all together
        add_vars([
            'title' => htmlspecialchars($post->title),
            'unpublished' => $unpublished,
            'post' => $post,
            'edit' => $edit
        ]);

        set_template((new PostManager())->getTemplate('post'));
    }

    public function init_permission($suffix) {
        $this->permission = "Posts_manage_posts";
        $this->permissionGroup = "Posts";
        $this->publishPermission = "Posts_publish_posts";
        if($suffix) {
            $this->permission .= "_$suffix";
            $this->publishPermission .= "_$suffix";
            $this->permissionGroup .= " $suffix";
        }
        $GLOBALS['POST_PERMISSIONS'] = $this->permission;
    }

    private function path(string $methodName, array $args = [], string $method = "get", $context = "web") {
        $className = $this::class;
        $path = get_path_from_route($className, $methodName, $args, $method, $context);
        if(!$path) {
            throw new UnknownError("Could not find '$methodName' route");
        }
        return $path;
    }

    private function getDisplaySession() {
        $validTypes = [
            'wide'   => true,
            'mixed'  => null,
            'narrow' => false,
        ];
        
        if(!key_exists($_GET['display'],$validTypes)) {
            unset($_SESSION['Posts_display_type']);
            exit;
        }
        $value = $validTypes[$_GET['display']];

        if($value === null) {
            unset($_SESSION['Posts_display_type']);
            return;
        }
        $_SESSION['Posts_display_type'] = $value;
    }

    function RSS_feed() {
        if(!$this->postMan) throw new Exception("The Post Controller is not initialized");
        $query = $this->getParams($this->postMan, ['published' => true]);
        $docs = $this->postMan->findAllAsSchema(...$query);

        header('Content-Type: application/rss+xml; charset=utf-8');

        $items = $this->docsToViews($docs, "/RSS/item.xml");
        echo view("/RSS/feed.xml", [
            'posts' => $items
        ]);
        exit;
    }
}
