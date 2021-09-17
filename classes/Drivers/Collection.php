<?php

namespace Drivers;

class Collection extends Database {
    function __construct($collection) {
        $this->c = $collection;
    }

    function get_collection_name() {
        return $this->c;
    }
}
