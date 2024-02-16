<?php

namespace Contact;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\BooleanResult;

class AdditionalContactFields extends PersistanceMap{
    function __get_schema():array {
        return [];
    }
}