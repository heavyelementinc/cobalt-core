<?php

namespace Cobalt\Documentation\Controllers;

use Cobalt\Controllers\ModelController;
use Cobalt\Model\Model;
use Cobalt\Documentation\Model\Documentation as ModelDocumentation;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;
use Routes\Router;

class Documentation extends ModelController {

    static $api_read_permission          = "Self";
    static $api_create_permission        = "Documentation_edit";
    static $api_update_permission        = "Documentation_edit";
    static $api_destroy_permission       = "Documentation_destroy";
    static $api_multidestroy_permission  = "Documentation_destroy";
    static $api_batch_archive_permission = "Documentation_destroy";
    static $api_archive_permission       = "Documentation_destroy";
    static $admin_index                  = "Documentation_edit";
    static $admin_new_document           = "Documentation_edit";
    static $admin_edit                   = "Documentation_edit";

    public static function defineModel(): Model {
        return new ModelDocumentation();
    }

    public function edit($document): string {
        return view("/Cobalt/Documentation/templates/admin/documentation-editor.php");
    }

    public function destroy(Model|BSONDocument $document): array {
        return [
            'dangerous' => true,
            'message' => "Are you sure you want to delete $document->title",
            'okay' => "Delete",
            'post' => $_POST,
        ];
    }

    public function list() {
        $route = parse_url($_GET['path'], PHP_URL_PATH);
        if(!$route) throw new BadRequest("Invalid referrer");
        
        // Prepare our new context
        $context = \Routes\Route::get_router_context($route);
        $router = new Router($context, "get");
        // Import existing routes and other balogna
        $router->get_routes();
        // Discover routes
        $details = $router->discover_route($route, null);
        
        // Look up the router by controller details
        $result = $this->model->findDocsByControllerName($details[1]['controller'], ['projection' => ['title' => 1]]);
        if(!$result) throw new NotFound("No matching documents found", true);
        $html = "";
        foreach($result as $doc) {
            $html .= "<li><a href=\"/documentation/read/$doc->_id\">$doc->title</a></li>";
        }
        add_vars(['title' => 'Documentation']);
        return <<<HTML
            <h1>Documentation</h1>
            <ul>$html</ul>
            HTML;
    }

    public function individual($id) {
        $_id = new ObjectId($id);
        /** @var ModelDocumentation $result */
        $result = $this->model->findOne(['_id' => $_id]);
        if(!$result) throw new NotFound("Not found", true);
        add_vars(["title" => "$result->title"]);
        return "<article><header><h1>$result->title</h1>Last Modified: <date-span value='".($result->body->lastModified("c"))."' from='c'></date-span></header>". view_from_string($result->body->render(), []) . "</article>";
    }
}