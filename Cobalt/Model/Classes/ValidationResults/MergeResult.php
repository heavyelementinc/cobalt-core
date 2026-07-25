<?php

namespace Cobalt\Model\Classes\ValidationResults;

class MergeResult {
    protected array $value = [];
    function __construct(array $value) {
        $this->value = $value;
    }

    public function get_value():array {
        return $this->value;
    }
}