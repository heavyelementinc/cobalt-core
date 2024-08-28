<?php

namespace Auth;

use Cobalt\Maps\PersistanceMap;
use Drivers\Database;

class UserFlagPersistance extends PersistanceMap{

    public function __set_manager(?Database $manager = null): ?Database {
        return null;
    }

    public function __get_schema(): array {
        return [
            
        ];
    }


    
}