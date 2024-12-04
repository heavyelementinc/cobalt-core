<?php

namespace Cobalt\Model\Types;

class FileType extends ModelType {
    public function filter($value) {
        // Let's carry out core validation first
        $value = parent::filter($value);
        return $value;
    }
}