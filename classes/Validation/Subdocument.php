<?php

namespace Validation;


class Subdocument extends Normalize {

    function __construct($values, $schema) {
        parent::__construct($values);
        $this->init_schema($schema);
    }

    public function __get_schema(): array {
        return [];
    }
}
