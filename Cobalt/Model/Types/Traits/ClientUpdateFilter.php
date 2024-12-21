<?php

namespace Cobalt\Model\Types\Traits;

trait ClientUpdateFilter {
    public array $updateParameters = [];

    public function __filter($value) {
        return $this->filter($value);
    }
}