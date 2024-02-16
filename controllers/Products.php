<?php

use Cobalt\Maps\GenericMap;
use Controllers\Traits\Createable;
use Controllers\Traits\Destroyable;
use Controllers\Traits\Readable;
use Controllers\Traits\Updateable;
use Drivers\Database;
use MongoDB\BSON\ObjectId;
use Validation\Normalize;

class Products {
    use Createable, Readable, Updateable, Destroyable;

    public function get_manager(): Database {
        return new 
    }

    public function create($id): ObjectId { }

    public function new_document($id) { }

    public function read($id): GenericMap|Normalize { }

    public function update($id): ObjectId { }

    public function destroy($id) { }

    
}