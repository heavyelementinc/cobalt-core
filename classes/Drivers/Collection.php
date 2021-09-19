<?php

namespace Drivers;

class Collection extends Database {
    function __construct($collection, $db = null) {
        $this->c = $collection;
        parent::__construct($db);
    }

    function get_collection_name() {
        return $this->c;
    }
}
