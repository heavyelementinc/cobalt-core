<?php

namespace Cobalt\Controllers\Traits;

use Cobalt\Model\Model;
use Exceptions\HTTP\NotFound;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

/**
 * @todo add a function that will automatically generate an edit page
 */
trait EditableModel {
    var $initialized = false;

    public $name;
    public Model $model;

}