<?php

namespace Cobalt\DataModel\Controllers;

use Cobalt\Database\Interfaces\DeleteResult;
use Cobalt\Database\Interfaces\InsertOneResult;
use Cobalt\Database\Interfaces\UpdateResult;
use Cobalt\DataModel\Controllers\Traits\ControllerIndex;
use Cobalt\DataModel\Filters\FilterResult;
use Cobalt\DataModel\Types\DocumentType;
use Error;
use MongoDB\BSON\ObjectId;

/**
 * @mixin DocumentType
 */
trait Controller {
    use ControllerIndex;
    function index(int $page, int $limit) {
        
    }

    /**
     * Returns the path to the form template for this document
     * @return string 
     */
    abstract function pathToFormView():string;

    function new() {

    }

    function edit(ObjectId $id) {

    }

    function create(DocumentType $document):InsertOneResult {
        throw new Error("Not Implemented");
    }

    function read(ObjectId $id):?DocumentType {
        throw new Error("Not Implemented");
    }

    function update(ObjectId $id, DocumentType $document):UpdateResult {
        throw new Error("Not Implemented");
    }

    function destroy(ObjectId $id):DeleteResult {
        throw new Error("Not Implemented");
    }

    // /**
    //  * 
    //  * @return array{}
    //  */
    // static function web():array {
    //     return [];
    // }

    /**
     * 
     * @return array{index:Object,edit:Object}
     */
    static function admin():array {
        return [];
    }

    /**
     * 
     * @return array{create:Object,read:Object,update:Object,destroy:Object}
     */
    static function apiv1():array {
        return [];
    }
}