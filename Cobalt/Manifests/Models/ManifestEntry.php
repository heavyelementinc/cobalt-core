<?php

namespace Cobalt\Manifests\Models;

use Cobalt\Model\GenericModel;

class Entry extends GenericModel {

    function __construct(array $value) {
        parent::__construct([
            'name' => [

            ]
        ], $value);
    }
}