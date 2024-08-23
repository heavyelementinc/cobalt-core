<?php

use Controllers\ClientFSManager;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Model\BSONDocument;

class ImageEditor {
    use ClientFSManager;
    private $database;
    
    function getDatabase() {
        $this->database = \db_cursor('', null, false, true);
        return;
    }

    function delete($id, $fieldName) {
        $this->getDatabase();
        $this->locateDocument($id, $fieldName);
    }

    function rename($id, $fieldName) {
        $this->getDatabase();
        $this->locateDocument($id, $fieldName);
    }

    /**
     * 
     * @param mixed $id 
     * @param mixed $fieldName 
     * @return array{document: null|BSONDocument|Persistable, collection: string, id: ObjectId}
     */
    function locateDocument($id, $fieldName):array {
        if(!$id) throw new NotFound("Request must specify an ID");
        $_id = new ObjectId($id);

        $collections = $this->database->getCollectionNames();
        
        foreach($collections as $collection) {
            continue;
        }

        return [
            'document' => '',
            'collection' => '',
            'id' => $_id
        ];
    }
}