<?php

namespace Contact;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\BooleanResult;
use Drivers\Database;

class AdditionalContactFields extends PersistanceMap{

    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }
    
    function __get_schema():array {
        return [];
    }
}