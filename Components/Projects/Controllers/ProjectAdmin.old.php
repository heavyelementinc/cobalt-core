<?php
/**
 * ProjectAdmin.php
 * @author Gardiner Bryant, Heavy Element
 * @copyright 2023 - Perry's Home Furnishings & Design
 */
namespace Components\Projects\Controllers;

use Components\Projects\Classes\ProjectManager;
use Components\Projects\Classes\ProjectSchema;
use Controllers\ClientFSManager;
use Controllers\Controller;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;

use Validation\Exceptions\ValidationFailed;

use const Dom\NOT_FOUND_ERR;

class ProjectAdmin extends Controller {
    use ClientFSManager;
    
    function __construct() {
        // parent::__construct();
        // Set the filename path
        $this->fs_filename_path = "/projects/";
    }

    function customer_index() {
        return $this->index("customer");
    }
    
    function shop_index() {
        return $this->index('shop');
    }

    private function index($type) {
        $manager = new ProjectManager();

        $data = [
            'customer' => [
                'query' => [
                    '$or' => [
                        ['shop' => false],
                        ['shop' => ['$exists' => false]]
                    ]
                ]
            ],
            'shop' => [
                'query' => [
                    'shop' => true
                ]
            ]
        ];

        $projects = $manager->findAllAsSchema(...$this->params($manager,
            $data[$type]['query'],
            [
            'defaultOptions' => [
                'sort' => ['order' => -1]
                ]
            ]
        ));
        add_vars([
            'title' => ucfirst($type) . " Projects",
            'type'  => $type,
            'projects' => view_each("/Components/Projects/templates/admin/index-item.html",$projects)
        ]);
        
        return view("/Components/Projects/templates/admin/index.html");
    }

    function editor($slug) {

        $manager = new ProjectManager();
        $project = $manager->findOneAsSchema(['_id' => new ObjectId($slug)]);
        add_vars([
            'title' => $project->name,
            'doc' => $project,
            'gallery' => $this->directoryListing('', 'gallery',['filter' => ['for' => $project->_id], 'options' => $manager->sortOrder()])
        ]);
        $route = route("\\Components\\Projects\\Controllers\\Projects@project", [$project->url]);
        register_user_bar_items(['_view' => "<a href='$route'>View</a>"]);
        return view("/Components/Projects/templates/admin/editor.php");
    }

    function newProject($type = "customer") {

        $doc = new ProjectSchema();
        $doc->__dataset['shop'] = false;
        if($type === "shop") $doc->__dataset['shop'] = true;

        add_vars([
            'title' => 'New Project',
            'doc' => $doc,
        ]);

        return view("/Components/Projects/templates/admin/editor.php");
    }

    function update($id = null) {
        $manager = new ProjectManager();
        $_id = new ObjectId($id);
        $this->initFS();
        $schema = new ProjectSchema(['_id' => $id,'image_count' => $this->fs->count(['for' => $_id])]);
        if(isset($_POST['name'])) $_POST['url'] = "";
        $validated = $schema->validate($_POST);
        unset($validated['_id']);

        if(is_null($id)) {
            $validated['order'] = $manager->count([]) + 1;
            $result = $manager->insertOne($validated);
            return header("X-Redirect: /admin/project/" . $result->getInsertedId());
        }

        $result = $manager->updateOne(['_id' => $_id],['$set' => $validated]);
        return $validated;
    }

    function attach($id) {
        try{
            $_id = new ObjectId($id);
        } catch (\Exception $e) {
            throw new BadRequest("That ID is invalid");
        }
        $manager = new ProjectManager();
        $doc = $manager->findOneAsSchema(['_id' => $_id]);
        if(is_null($doc)) throw new NotFound("That project doesn't exist");
        
        
        $images = [];
        if(isset($doc->__dataset['image'])) $images = $doc->__dataset['images']->getArrayCopy() ?? [];
        $imageResult = $this->clientUploadImagesAndThumbnails('attach', 600, null, ['for' => $_id]);

        array_push($images, ...$imageResult);
        // $merged = array_merge($images, $imageResult);

        $set = [
            'images' => $images,
            'image_count' => $this->fs->count(['for' => $_id])
        ];
        if(!$doc->primary) $set['primary'] = "/res/fs" . $images[0]['media']['filename'];
        $result = $manager->updateOne(['_id' => $_id],['$set' => $set]);

        // return header("X-Refresh: now");
        // header("X-Redirect: /admin/project/" . (string)$_id);
        update("cobalt-listing", [
            'outerHTML' => $this->directoryListing('', 'gallery',['filter' => ['for' => $_id], 'options' => $manager->sortOrder()])
        ]);
        update("@form", ['clear' => 'clear']);
    }

    function sort() {
        return $this->updateSortOrder($_POST);
    }

    function setAsDefault($id, $default) {
        $this->initFS();
        $fs = $this->fs;
        $dft = new ObjectId($default);
        $result = $fs->findOne(['_id' => $dft]);
        if(!$result) throw new NotFound("Image not found");
        $_id = new ObjectId($id);
        $man = new ProjectManager();
        $doc = $man->findOne(['_id' => $_id]);
        if(!$doc) throw new NotFound("Project not found");
        $man->updateOne(['_id' => $_id], ['$set' => ['primary' => "/res/fs".$result->filename]]);
        return $result;
    }

    function deleteAttachment($project_id, $attachment_id) {
        $project_id = new ObjectId($project_id);
        $attachment_id = new ObjectId($attachment_id);

        
        $this->initFS();
        $attachment = $this->fs->findOne(['_id' => $attachment_id]);

        $man = new ProjectManager();
        // Look up the current project
        $doc = $man->findOne(['_id' => $project_id]);

        // Determine if the file we're deleting is the current primary file.
        if($doc->primary === "/res/fs" . $attachment->filename) {
            // throw new ValidationFailed();
            return header("X-Modal: @warning <h1>Sorry</h1>The image you're trying to delete is currently set as the primary image.<br><br>Choose another image as the primary image before deleting this one.");
            // $result = $this->fs->find(['for' => $project_id, '$exists' => ['isThumbnail' => false]]);
            // $result = iterator_to_array($result);
            // $update['$set'] = ['primary' => $result[0]->filename];
        }
        

        // Delete the file from the database
        $this->delete($attachment_id);

        $update = [
            '$pull' => ['images' => ['id' => $attachment_id]],
            '$inc' => ['image_count' => -1]
        ];

        if($doc) {    
            $doc = $man->updateOne(["_id" => $project_id], $update);
        }
        
        return header("X-Redirect: /admin/project/" . (string)$project_id);
    }

    function renameImage($id, $image) {
        $rename = $_POST['rename'];
        // Let's check if the project we're referencing exists.
        $man = new ProjectManager();
        $project = $man->project(new ObjectId($id));
        if(!$project) throw new NotFound("Not found");
        
        // Now let's make sure the file we're looking for also exists.
        $this->initFS();
        $imageData = $this->fs->findOne(['_id' => new ObjectId($image)]);
        if(!$imageData) throw new NotFound("That file does not exist");

        // Let's rename our file
        $results = $this->renameFile($image, $rename);

        // Let's see if this file was set as the primary for this project
        if($project->primary === "/res/fs$imageData->filename") {

            // If it was, update this project with the new filename
            $man->updateOne(['_id' => $project->_id],[
                '$set' => [
                    'primary' => $results['name']
                ]
            ]);
        }

        // Return the file rename
        return $results;
    }

    function deleteProject($id) {
        $manager = new ProjectManager();
        $_id = new ObjectId($id);
        $doc_query = ['_id' => $_id];
        $image_query = ['for' => $_id];
        $project = $manager->findOne($doc_query);
        if(!$project) throw new NotFound(NOT_FOUND_ERR);
        $this->initFS();
        $images = $this->fs->count($image_query);
        confirm("This operation will delete 1 project and $images image" . plural($images) . ". Are you sure you want to continue?", $_POST, "Confirm Deletion", true);
        $this->deleteAllBelongingToId($_id);
        $result = $manager->deleteOne($doc_query);
        redirect("/admin/project/customer");
        return $result->getDeletedCount();
    }

    // TODO: Implement renaming
    // function rename($id) {
    //     $this->fs->rename();
    // }
}