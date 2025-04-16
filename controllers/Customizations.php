<?php

use Cobalt\Customization\CustomizationManager;
use Cobalt\Customization\CustomSchema;
use Controllers\ClientFSManager;
use Drivers\FileSystem;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class Customizations extends \Controllers\Controller {
    use ClientFSManager;
    var $man = null;

    function __construct() {
        $this->man = new CustomizationManager();
    }

    function index($groupName = null) {
        if($groupName === "edit") header("Location: /admin/customizations/");
        $query = [];
        if($groupName !== null) $query['group'] = ['$in' => explode(";",$groupName)];
        $regex = new \MongoDB\BSON\Regex($_GET['search']);
        $this->enableSearchField('search', [
            '$or' => [
                ['value' => $regex],
                ['unique_name' => $regex],
                ['name' => $regex],
            ]
        ]);
        $result = $this->man->findAllAsSchema(...$this->params($this->man,$query));
        if(has_permission("Customizations_create")) $create_button = '<a href="/admin/customizations/edit/new" class="floater--new-item"></a>';

        $table = "";
        foreach($result as $doc) {
            $table .= view('/customizations/index/individual.html',[
                'doc' => $doc, 
                'embed' => ($doc['type'] === "text") ? "" : ".embed",
                'exclaim' => ($doc['type'] === "text") ? "" : "!",
            ]);
        }

        add_vars([
            'title' => 'Customizations',
            'pagination' => $this->getPaginationLinks(),
            'table' => $table,
            'create_button' => $create_button,
        ]);

        return set_template('/customizations/index/index.html');
    }

    function editor($id, $edit = false) {
        // if($id === "new" && !has_permission("Customizations_create")) throw new Unauthorized("You cannot cre")
        if($id === "new") $_id = new ObjectId();
        else $_id = new ObjectId($id);
        
        $result = $this->man->findOneAsSchema(['_id' => $_id]);
        if($id === "new") {
            if($result === null) $result = new CustomSchema();
            else throw new NotFound("That resource is unavailable");
        }

        add_vars([
            'title' => $result->name ?? "New Customization",
            'group_options' => $this->man->group_options(),
            'doc' => $result,
            'value' => $result->value ?? ""
        ]);

        $view = $result->getTemplate($edit);
        return set_template($view);
    }

    function modify_customization($id) {
        $result = $this->editor($id, true);
        header("X-Refresh: now");
        return $result;
    }

    function update($id = null) {
        $_id = new ObjectId($id);
        $new = false;
        if($id === null) $new = true;

        if($new && !has_permission("Customizations_create")) throw new Unauthorized("You don't have permission to create new customiziation entries");
        if(!has_permission("Customizations_modify")) {
            $mutable_fields = ['group', 'value'];
            $fields = array_keys($_POST);
            foreach($fields as $field) {
                if(!in_array($field, $mutable_fields)) throw new Unauthorized("You do not have sufficient privileges to modify the '".htmlspecialchars($field)."' field.");
            }
        }

        $resource = $this->man->findOne(['_id' => $_id]);

        $validate = new CustomSchema();
        if($resource) $validate->allowNameCollision($resource['unique_name']);
        $valid = $validate->validate($_POST);
        
        $update = $this->process_values($valid);

        $result = $this->man->updateOne(['_id' => $_id], $update, ['upsert' => $new]);

        if($new === true) header("X-Redirect: /admin/customizations/edit/". (string)$_id);

        return $valid;
    }

    private function process_values($valid) {
        $update = ['$set' => $valid];

        // if(key_exists('value', $valid)) {
        //     $update['$push'] = [
        //         'value' => $valid['value'],
        //         'session' => [
        //             'session_id' => session('_id'),
        //             'time' => new UTCDateTime(),
        //         ]
        //     ];
        //     unset($update['$set']['value']);
        //     if(empty($update['$set'])) unset($update['$set']);
        // }
        return $update;
    }

    function uploadFile($id) {
        if(empty($_FILES)) return $this->update($id);
        $this->fs_filename_path = "customization";
        $_id = new ObjectId($id);
        $verify = $this->man->findOne(['_id' => $_id]);
        if(!$verify) throw new NotFound("Resource is unavailable");
        $data = $_POST;
        if($_FILES['value']) {
            $data = $this->clientUploadFile('value', 0, [
                'for' => $_id,
                'customization' => true,
            ]);
        } else {
            throw new BadRequest("Unexpected data in request.");
        }
        $file = "/res/fs/$data[filename]";
        unset($data['filename']);
        $reformat = [
            'value' => $file,
            'meta' => $data,
        ];
        $valid = $this->process_values($reformat);
        $this->man->updateOne(['_id' => $_id], $valid);
        return $reformat;
    }

    function deleteItem($id = null) {
        $_id = new ObjectId($id);
        $search = $this->man->findOne(['_id' => $_id]);
        if(!$search) throw new NotFound("That resource is not available");
        $result = $this->man->deleteOne(['_id' => $_id]);
        update("[action='/api/v1/customizations/$id']", ['remove' => "[action='/api/v1/customizations/$id']:closest(flex-row)"]);
        return $result->getDeletedCount();
    }

    // function deleteImage($id = null ){
    //     $_id = new ObjectId($id);
    //     $fs = new FileSystem();
    //     $result = $fs->findOne(['_id' => $_id]);
    //     $fs->delete($_id);
    // }

    function list() {
        $all = $this->man->findAllAsSchema([
            // ['meta.custom' => true]
            'type' => 'image'
        ], [
            'limit' => 300
        ]);

        $opts = [];
        $keys = [];

        foreach($all as $opt) {
            $opts[(string)$opt['_id']] = $opt;
            array_push($keys, $opt->_id);
        }

        $fs = new FileSystem();

        $files = $fs->find([
            'for' => ['$in' => $keys]
        ], [
            'limit' => 400
        ]);

        $html = [];
        foreach($files as $option) {
            $key = "orphan";
            if(key_exists((string)$option->for, $opts)) $key = (string)$option->for;
            $current_status = ((string)$option->_id === $key) ? "current-selection--this" : "orphan";
            if(!key_exists($key, $html)) $html[$key] = "<div><h1>".$opts[$key]->name ." | $key</h1>";
            $html[$key] .= "<a href='/admin/customizations/edit/$option->for' class='block-link $current_status' style=\"background-image:url('/res/fs/$option->filename'); color: ".$option->meta->contrast_color.";background-color: ".$option->meta->accent_color.";\">
                <action-menu type='options'>
                    <option method='DELETE' action='/api/v1/customizations/attachment/$option->_id/'>Delete</option>
                </action-menu>
            </a>";
        }
        
        add_vars([
            'title' => "Listing all custom files",
            'html' => implode($html) . "</div>"
        ]);

        return set_template('/customizations/index/list.html');
    }

    function resetItem($id) {
        $query = ['_id' => new ObjectId($id)];
        $result = $this->man->findOneAsSchema($query);
        if(!$result) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
        
        $items = $this->man->load();
        foreach($items as $item) {
            if($item['unique_name'] == $result->unique_name) {
                $update = $this->man->updateOne($query, ['$set' => $item], ['upsert' => false]);
                header("X-Refresh: now");
                // header("X-Redirect: " . route("Customizations@editor", [(string)$id]));
                return;
            }
        }
        throw new BadRequest("This customization does not exist in the definition file! (Was the unique name changed?)", true);
    }

    function resetAll() {
        confirm("This will reset ALL customizations to their default state. Are you sure you want to continue?\n\n<strong>THIS WILL RESULT IN DATA LOSS.</strong>", $_POST, 'Continue', true);
        $this->man->import(true);
    }
}
