<?php

namespace Cobalt\Model\Traits;

trait Filterable {
    public array $updateParameters = [];

    public function __filter($value) {
        return $this->filter($value);
    }
}