<?php
/** Using the Post Contoller:
 * Have your controller extend PostController,
 * In your controller constructor, call $this->initialize("<the Post's DB collection>", "the Post's schema");
 * Once you've done that, you only need to set up your routes to call `YourPostController@update`, etc.
 * 
 * HOWEVER, do note that in order to create a Post, you'll need to create a Post
 */
namespace Controllers;

use Posts\PostManager;
use Exception;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use Exceptions\HTTP\UnknownError;

abstract class PostController extends Controller {
    public $postMan = null;
    protected $permission = "Post_manage_posts";
    protected $publishPermission = "Post_publish_posts";
    protected $permissionGroup = "Post";

    public function initialize($collection, $schemaName = null, $permission_suffix = "") {
        if($schemaName === null) $schemaName =  "\\Posts\\PostSchema";
        $this->init_permission($permission_suffix);
        
        // Initialize our Post controller
        $this->postMan = new PostManager(null, $collection);
        $this->postMan->set_schema($schemaName);

    }

    public function admin_index() {
        if(!$this->postMan) throw new Exception("You must manually initialize the PostController");
        $result = $this->postMan->findAllAsSchema(...$this->getParams($this->postMan, []));
        $posts = "";
        foreach($result as $post) {
            $posts .= with($post->getTemplate("table"),[
                'post' => $post,
                'editor_route' => $this->path('edit',[(string)$post['_id']],"get","admin"),
            ]);
        }
        add_vars([
            'title' => ($this->customTitle) ? $this->customTitle . " Admin Panel" : 'Posts Admin Panel',
            'posts' => $posts,
            'controls' => $this->getPaginationControls(),
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
            'update_action' => $this->path('update',[$id],'put',   'apiv1'),
            'upload_action' => $this->path('upload',[$id],'post',  'apiv1'),
            'delete_action' => $this->path('delete',[$id],'delete','apiv1')
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
        $validation = $schema();
        $mutant = $validation->validate($_POST);
        $_id = $this->postMan->__id($id);
        // Find our post
        $this->postMan->updateOne(['_id' => $_id],[
            '$set' => $mutant
        ],['upsert' => true]);

        // We want to redirect new entries to the appropriate page
        if($id === null) header("X-Redirect: " . $this->path('update',[(string)$_id]),"get","admin");
        return $mutant;
    }

    public function delete($id) {
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

    }

    public function init_permission($suffix) {
        $this->permission = "Post_manage_posts";
        $this->permissionGroup = "Post";
        $this->publishPermission = "Post_publish_posts";
        if($suffix) {
            $this->permission .= "_$suffix";
            $this->publishPermission .= "_$suffix";
            $this->permissionGroup .= " $suffix";
        }
    }

    public function path(string $methodName, array $args = [], string $method = "get", $context = "web") {
        $className = $this::class;
        $path = get_path_from_route($className, $methodName, $args, $method, $context);
        if(!$path) {
            throw new UnknownError("Could not find '$methodName' route");
        }
        return $path;
    }
}