<?php

namespace Cobalt\Controllers\Traits;

use Cobalt\Model\Model;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;

trait DestroyableModel {
    var $initialized = false;

    public $name;
    public Model $model;

    /** 
     * The return value of this method will be the prompt for the client
     * Return value must be an array with at least the first key:
     *  * `message` <string> The confirmation message to be displayed to the client
     *  * `post` <array> The data to re-POST to this endpoint
     *  * `okay` <string> The acknowledge/affirmative button label
     *  * `dangerous` <bool> Whether or not this action is dangerous
     * @return array{message: string, post: array{poop: bool}, okay: string, dangerous: bool}
    */
    abstract function destroy(Model|BSONDocument $document):array;

    public function __destroy($id) {
        $read = $this->__read($id);
        if(!$read) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
        $default_confirm_message = "Are you sure you want to delete this record?";
        
        $confirm_message = $this->destroy($read);

        confirm($confirm_message['message'] ?? $confirm_message[0] ?? $default_confirm_message, $confirm_message['post'] ?? $_POST, $confirm_message['okay'] ?? "Yes", $confirm_message['dangerous'] ?? true);
        
        $_id = new ObjectId($id);
        $result = $this->model->deleteOne(['_id' => $_id]);
        header("X-Redirect: " . route("$this->name@__index"));
        return $result->getDeletedCount();
    }

    public function __multidestroy() {
        $upgraded = [];
        foreach($_POST[CRUDABLE_MULTIDESTROY_FIELD] as $id) {
            if(!$id) throw new BadRequest("Invalid ID found", "Invalid ID supplied");
            $upgraded[] = new ObjectId($id);
        }
        $query = ['_id' => ['$in' => $upgraded]];
        $results = $this->model->count($query);
        confirm("This will delete $results document".plural($results).". Do you want to continue?", $_POST);

        $deleted = $this->model->deleteMany($query);
        header("X-Redirect: " . route("$this->name@__index"));
    }

    public function __archive($id) {
        $read = $this->__read($id);
        if(!$read) throw new NotFound(ERROR_RESOURCE_NOT_FOUND);
        $un = "";
        $update_operator = '$set';
        if($read->__isArchived()) {
            $update_operator = '$unset';
            $un = "un";
        }
        $default_confirm_message = "Are you sure you want to $un"."archive this record?";
        // $confirm_message = $this->archive($)
        confirm($default_confirm_message, $_POST, "Archive", false);
        $result = $this->model->updateOne(['_id' => $read->_id], [$update_operator => [CRUDABLE_ARCHIVED_FIELD => new UTCDateTime()]]);
        // header("X-Redirect: " . route("$this->name@__index"));
        return $result->getModifiedCount();
    }

    public function __archive_batch() {
        $upgraded = [];
        foreach($_POST[CRUDABLE_MULTIDESTROY_FIELD] as $id) {
            if(!$id) throw new BadRequest("Invalid ID found", "Invalid ID supplied");
            $upgraded[] = new ObjectId($id);
        }
        $query = ['_id' => ['$in' => $upgraded]];
        $results = $this->model->count($query);

        $find = $this->model->find($query);
        $updates = ['archived' => [], 'unarchived' => []];

        foreach($find as $doc) {
            if($doc->__isArchived()) {
                $updates['archived'][] = $doc->_id;
            } else {
                $updates['unarchived'][] = $doc->_id;
            }
        }
        $archived = count($updates['archived']);
        $unarchived = count($updates['unarchived']);

        if($archived > 0 && $unarchived > 0) {
            $batch_message = "This batch action will archive all $unarchived unarchived document" .plural($unarchived) . " while also unarchiving all $archived document". plural($archived).". Are you sure you want to perform this action?";
        } else if($archived > 0){
            $batch_message = "This action will unarchive $archived document".plural($archived).". Do you want to continue?";
        } else {
            $batch_message = "This action will archive $unarchived document".plural($unarchived).". Do you want to continue?";
        }

        confirm($batch_message, $_POST, "Archive");
        if($unarchived > 0) {
            $archived = $this->model->updateMany(['_id' => ['$in' => $updates['unarchived']]], ['$set' => [CRUDABLE_ARCHIVED_FIELD => new UTCDateTime()]]);
        } 
        if ($archived > 0) {
            $unarchived = $this->model->updateMany(['_id' => ['$in' => $updates['archived']]], ['$unset' => [CRUDABLE_ARCHIVED_FIELD => true]]);
        }
        header("X-Redirect: " . route("$this->name@__index"));
    }


    static public function route_details_destroy():array {
        return [];
    }

}