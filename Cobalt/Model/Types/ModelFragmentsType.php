<?php

namespace Cobalt\Model\Types;

use Cobalt\Model\Attributes\Directive;

class ModelFragmentsType extends ModelType {
    #[Directive()]
    public function defineSchema(array $schema):ModelType {
        $this->__defineDirective('schema', $schema);
        return $this;
    }
}