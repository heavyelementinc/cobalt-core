<?php

namespace Cobalt\Posts;

use Cobalt\Maps\PersistanceMap;

class PostPersistance extends PersistanceMap {

    public function __get_schema(): array {
        return [
            'author' => new UserAccountResult
        ];
    }
    
}