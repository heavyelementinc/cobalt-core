<?php

use Controllers\Controller;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;

class ExtensionsController extends Controller {
    function index() {
        $EXTENSION_MANAGER = extensions();
        $EXTENSION_MANAGER->build_extension_list();
        $extensions = "";
        foreach($EXTENSION_MANAGER->find(['is_extension' => true]) as $ext) {
            $extensions .= view("/cobalt/extensions/each.html", [
                'doc' => $ext,
                'path' => $EXTENSION_MANAGER->sanitize_install_path($ext['install_path']),
                "settings" => count($ext['settings']),
                "permissions" => count($ext['permissions']),
            ]);
        }
        add_vars([
            'title' => 'Extension Manager',
            "extensions" => ($extensions) ? $extensions : "<flex-cell span='6'>No extensions installed</flex-cell>",
            "extman" => $EXTENSION_MANAGER,
        ]);
        set_template("/cobalt/extensions/index.html");
    }

    function extension($id) {
        $_id = new ObjectId($id);
        $ext = extensions()->findOne(['_id' => $_id]);

        if(!$ext) throw new NotFound("The requested extension does not exist!");
        
        $view = $ext->view ?? "/cobalt/extensions/view.html";
        if(!$ext['last_updated']) $ext['last_updated'] = filemtime($ext['install_path']) * 1000;

        $grants = extensions()->get_grants($ext);

        add_vars([
            'title' => $ext->meta->name ?? $ext->class,
            'path' => extensions()->sanitize_install_path($ext['install_path']),
            'doc' => $ext,
            'options_view' => view($ext->options_view ?? "/cobalt/extensions/options_view.html", ['doc' => $ext]),
            'settings_link' => "",
            'settings_form' => "",
            'grants' => $grants,
        ]);

        set_template($view);
    }

    function modify_extension_state($id) {
        $EXTENSION_MANAGER = extensions();

        $_id = new ObjectId($id);
        if(!isset($_POST['active'])) throw new BadRequest("Unexpected data contained in request.");
        $ext = $EXTENSION_MANAGER->updateOne(['_id' => $_id], ['$set' => ['active' => $_POST['active']]]);
        return $ext->getModifiedCount();
    }

    // TODO: Add validation
    function modify_extension_options($id) {
        $EXTENSION_MANAGER = extensions();
        $_id = new ObjectId($id);
        $ext = $EXTENSION_MANAGER->updateOne(['_id' => $_id], ['$set' => $_POST]);
        return $ext->getModifiedCount();
    }

    function rebuild_database() {
        extensions()->build_extension_list();
        header("X-Refresh: now");
    }
}
